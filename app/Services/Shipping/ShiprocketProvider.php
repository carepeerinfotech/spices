<?php

namespace App\Services\Shipping;

use App\Contracts\ShippingProvider;
use App\Models\Order;
use App\Models\Shipment;
use App\Models\ShipmentLog;
use App\Services\Settings\SettingsService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class ShiprocketProvider implements ShippingProvider
{
    private string $base = 'https://apiv2.shiprocket.in/v1/external';

    public function __construct(private SettingsService $settings) {}

    public function testConnection(): array
    {
        try {
            $token = $this->token(true);

            return $token
                ? ['success' => true, 'message' => 'Connected to Shiprocket successfully.']
                : ['success' => false, 'message' => 'Unable to authenticate with Shiprocket.'];
        } catch (\Throwable $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function serviceability(string $pickupPincode, string $deliveryPincode, float $weightKg, float $codAmount = 0): array
    {
        $response = $this->http()->get($this->base.'/courier/serviceability/', [
            'pickup_postcode' => $pickupPincode,
            'delivery_postcode' => $deliveryPincode,
            'weight' => max($weightKg, 0.5),
            'cod' => $codAmount > 0 ? 1 : 0,
        ])->json();

        $couriers = collect(data_get($response, 'data.available_courier_companies', []))
            ->map(fn ($c) => [
                'courier_id' => (string) ($c['courier_company_id'] ?? ''),
                'courier_name' => $c['courier_name'] ?? 'Courier',
                'rate' => (float) ($c['rate'] ?? 0),
                'etd' => (int) ($c['estimated_delivery_days'] ?? ($c['etd'] ?? 5)),
                'etd_text' => $c['etd'] ?? null,
                'cod' => (bool) ($c['cod'] ?? false),
            ])
            ->sortBy('rate')
            ->values();

        if ($couriers->isEmpty()) {
            return ['success' => false, 'message' => 'Delivery not available for this pincode.', 'couriers' => []];
        }

        return [
            'success' => true,
            'message' => 'Serviceable',
            'couriers' => $couriers->all(),
            'cheapest' => $couriers->first(),
        ];
    }

    public function createOrder(Order $order): Shipment
    {
        $payload = ['order_id' => $order->order_number] + $this->orderPayload($order);

        $response = $this->http()->post($this->base.'/orders/create/adhoc', $payload)->json();

        if (! data_get($response, 'order_id') && ! data_get($response, 'shipment_id')) {
            throw new \RuntimeException(data_get($response, 'message', 'Unable to create Shiprocket order.'));
        }

        $shipment = Shipment::create([
            'order_id' => $order->id,
            'provider' => 'shiprocket',
            'provider_order_id' => (string) data_get($response, 'order_id'),
            'shipment_id' => (string) data_get($response, 'shipment_id'),
            'status' => 'created',
            'request_payload' => $payload,
            'response_payload' => $response,
        ]);

        $this->log($shipment, 'create_order', 'created', 'Shiprocket order created.');

        return $shipment;
    }

    public function updateOrder(Order $order, Shipment $shipment): Shipment
    {
        if ($shipment->awb_code) {
            throw new \RuntimeException('This shipment already has an AWB assigned, so Shiprocket no longer allows editing order/address details. Cancel and recreate the shipment instead.');
        }

        $payload = ['order_id' => $order->order_number] + $this->orderPayload($order);

        $response = $this->http()->post($this->base.'/orders/update/adhoc', $payload)->json();

        if (! data_get($response, 'success')) {
            throw new \RuntimeException(data_get($response, 'message', 'Unable to update Shiprocket order.'));
        }

        $shipment->update([
            'shipment_id' => (string) (data_get($response, 'shipment_id') ?: $shipment->shipment_id),
            'request_payload' => $payload,
            'response_payload' => $response,
            'last_error' => null,
        ]);

        $this->log($shipment, 'update_order', $shipment->status, 'Shiprocket order updated.');

        return $shipment->fresh();
    }

    private function orderPayload(Order $order): array
    {
        $pickup = $this->settings->get('shiprocket', 'pickup_location', 'Primary');
        $billingAddress = $order->billing_address ?: $order->shipping_address;
        $shippingAddress = $order->shipping_address;

        return [
            'order_date' => $order->created_at?->format('Y-m-d H:i') ?? now()->format('Y-m-d H:i'),
            'pickup_location' => $pickup,
            'billing_customer_name' => $order->billing_name ?: $order->customer_name,
            'billing_last_name' => '',
            'billing_address' => $billingAddress,
            'billing_address_2' => $this->addressLine2($billingAddress, $order->billing_postal_code ?: $order->shipping_postal_code),
            'billing_city' => $order->billing_city ?: $order->shipping_city,
            'billing_pincode' => $order->billing_postal_code ?: $order->shipping_postal_code,
            'billing_state' => $order->billing_state ?: $order->shipping_state,
            'billing_country' => $this->countryName($order->billing_country),
            'billing_email' => $order->billing_email ?: $order->customer_email,
            'billing_phone' => $order->billing_phone ?: $order->customer_phone,
            'shipping_is_billing' => (bool) $order->billing_same_as_shipping,
            'shipping_customer_name' => $order->customer_name,
            'shipping_address' => $shippingAddress,
            'shipping_address_2' => $this->addressLine2($shippingAddress, $order->shipping_postal_code),
            'shipping_city' => $order->shipping_city,
            'shipping_pincode' => $order->shipping_postal_code,
            'shipping_state' => $order->shipping_state,
            'shipping_country' => $this->countryName($order->shipping_country),
            'shipping_email' => $order->customer_email,
            'shipping_phone' => $order->customer_phone,
            'order_items' => $order->items->map(fn ($item) => [
                'name' => $item->product_name.($item->variant_label ? ' - '.$item->variant_label : ''),
                'sku' => $item->product_sku,
                'units' => $item->quantity,
                'selling_price' => (float) $item->price,
            ])->values()->all(),
            'payment_method' => $order->payment_method === 'cod' ? 'COD' : 'Prepaid',
            'sub_total' => (float) $order->subtotal,
            'length' => 10,
            'breadth' => 10,
            'height' => 10,
            'weight' => max((float) ($order->shipping_weight ?: 0.5), 0.5),
        ];
    }

    public function assignAwb(Shipment $shipment, ?string $courierId = null): Shipment
    {
        $payload = [
            'shipment_id' => [(int) $shipment->shipment_id],
        ];
        if ($courierId) {
            $payload['courier_id'] = (int) $courierId;
        }

        $response = $this->http()->post($this->base.'/courier/assign/awb', $payload)->json();
        $awb = data_get($response, 'response.data.awb_code') ?? data_get($response, 'awb_code');

        if (! $awb) {
            $shipment->update(['last_error' => json_encode($response), 'response_payload' => $response]);
            throw new \RuntimeException('Unable to assign AWB.');
        }

        $shipment->update([
            'awb_code' => $awb,
            'courier_id' => (string) (data_get($response, 'response.data.courier_company_id') ?? $courierId),
            'courier_name' => data_get($response, 'response.data.courier_name'),
            'status' => 'awb_assigned',
            'response_payload' => $response,
            'last_error' => null,
        ]);
        $this->log($shipment, 'assign_awb', 'awb_assigned', 'AWB assigned.');

        return $shipment->fresh();
    }

    public function generatePickup(Shipment $shipment): Shipment
    {
        $response = $this->http()->post($this->base.'/courier/generate/pickup', [
            'shipment_id' => [(int) $shipment->shipment_id],
        ])->json();

        $shipment->update([
            'status' => 'pickup_scheduled',
            'picked_up_at' => now(),
            'response_payload' => $response,
        ]);
        $shipment->order->update([
            'status' => 'shipped',
            'courier_name' => $shipment->courier_name,
        ]);
        $this->log($shipment, 'generate_pickup', 'pickup_scheduled', 'Pickup requested.');

        return $shipment->fresh('order');
    }

    public function track(Shipment $shipment): array
    {
        if (! $shipment->awb_code) {
            return [];
        }

        $response = $this->http()->get($this->base.'/courier/track/awb/'.$shipment->awb_code)->json();
        $shipment->update(['tracking_data' => $response]);

        return $response;
    }

    public function getOrderDetails(Shipment $shipment): array
    {
        if (! $shipment->provider_order_id) {
            return [];
        }

        $response = $this->http()->get($this->base.'/orders/show/'.$shipment->provider_order_id)->json();

        return data_get($response, 'data', $response ?? []);
    }

    public function cancel(Shipment $shipment): Shipment
    {
        $response = $this->http()->post($this->base.'/orders/cancel/shipment/awbs', [
            'awbs' => [$shipment->awb_code],
        ])->json();

        $shipment->update([
            'status' => 'cancelled',
            'response_payload' => $response,
        ]);
        $this->log($shipment, 'cancel', 'cancelled', 'Shipment cancelled.');

        return $shipment->fresh();
    }

    public function createReturn(Shipment $shipment): Shipment
    {
        $order = $shipment->order()->with('items')->firstOrFail();
        $payload = [
            'order_id' => $order->order_number.'-R',
            'order_date' => now()->format('Y-m-d H:i'),
            'channel_id' => $this->settings->get('shiprocket', 'channel_id'),
            'pickup_customer_name' => $order->customer_name,
            'pickup_address' => $order->shipping_address,
            'pickup_city' => $order->shipping_city,
            'pickup_state' => $order->shipping_state,
            'pickup_country' => 'India',
            'pickup_pincode' => $order->shipping_postal_code,
            'pickup_email' => $order->customer_email,
            'pickup_phone' => $order->customer_phone,
            'shipping_customer_name' => $this->settings->get('commerce', 'store_name', 'Elephant Shop'),
            'shipping_address' => 'Warehouse',
            'shipping_city' => 'New Delhi',
            'shipping_state' => 'Delhi',
            'shipping_country' => 'India',
            'shipping_pincode' => $this->settings->get('commerce', 'pickup_pincode', '110001'),
            'shipping_email' => $this->settings->get('commerce', 'support_email'),
            'shipping_phone' => '9999999999',
            'order_items' => $order->items->map(fn ($item) => [
                'name' => $item->product_name,
                'sku' => $item->product_sku,
                'units' => $item->quantity,
                'selling_price' => (float) $item->price,
            ])->values()->all(),
            'payment_method' => 'Prepaid',
            'sub_total' => (float) $order->subtotal,
            'length' => 10,
            'breadth' => 10,
            'height' => 10,
            'weight' => max((float) ($order->shipping_weight ?: 0.5), 0.5),
        ];

        $response = $this->http()->post($this->base.'/orders/create/return', $payload)->json();
        $shipment->update([
            'status' => 'return_initiated',
            'request_payload' => $payload,
            'response_payload' => $response,
        ]);
        $this->log($shipment, 'create_return', 'return_initiated', 'Return shipment created.');

        return $shipment->fresh();
    }

    public function pickupLocations(): array
    {
        $response = $this->http()->get($this->base.'/settings/company/pickup')->json();

        return data_get($response, 'data.shipping_address', []);
    }

    private function addressLine2(?string $address, ?string $postalCode): string
    {
        if ($address && preg_match('/\d/', $address)) {
            return '';
        }

        return $postalCode ? 'PIN '.$postalCode : '';
    }

    private function countryName(?string $country): string
    {
        if (! $country) {
            return 'India';
        }

        return config('countries.'.$country, $country);
    }

    private function token(bool $force = false): string
    {
        $cacheKey = 'shiprocket.token';
        if (! $force && Cache::has($cacheKey)) {
            return (string) Cache::get($cacheKey);
        }

        $email = $this->settings->get('shiprocket', 'email');
        $password = $this->settings->get('shiprocket', 'password');

        if (! $email || ! $password) {
            throw new \RuntimeException('Shiprocket credentials are missing.');
        }

        $response = Http::asJson()->post($this->base.'/auth/login', [
            'email' => $email,
            'password' => $password,
        ])->json();

        $token = data_get($response, 'token');
        if (! $token) {
            throw new \RuntimeException(data_get($response, 'message', 'Shiprocket auth failed.'));
        }

        Cache::put($cacheKey, $token, now()->addDays(9));

        return $token;
    }

    private function http()
    {
        return Http::withToken($this->token())->acceptJson()->asJson();
    }

    private function log(Shipment $shipment, string $action, string $status, string $message): void
    {
        ShipmentLog::create([
            'shipment_id' => $shipment->id,
            'order_id' => $shipment->order_id,
            'action' => $action,
            'status' => $status,
            'message' => $message,
            'payload' => $shipment->response_payload,
        ]);
    }
}
