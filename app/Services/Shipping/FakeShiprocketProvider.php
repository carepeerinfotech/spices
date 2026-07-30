<?php

namespace App\Services\Shipping;

use App\Contracts\ShippingProvider;
use App\Models\Order;
use App\Models\Shipment;
use App\Models\ShipmentLog;
use Illuminate\Support\Str;

class FakeShiprocketProvider implements ShippingProvider
{
    public function testConnection(): array
    {
        return ['success' => true, 'message' => 'Fake Shiprocket provider is ready.'];
    }

    public function serviceability(string $pickupPincode, string $deliveryPincode, float $weightKg, float $codAmount = 0): array
    {
        if (! preg_match('/^\d{6}$/', $deliveryPincode)) {
            return ['success' => false, 'message' => 'Enter a valid 6-digit pincode.', 'couriers' => []];
        }

        // Simulate unserviceable remote codes
        if (str_starts_with($deliveryPincode, '000')) {
            return ['success' => false, 'message' => 'Delivery not available for this pincode.', 'couriers' => []];
        }

        $rate = max(49, round(40 + ($weightKg * 25), 2));
        $etd = 3 + ((int) substr($deliveryPincode, -1) % 3);

        return [
            'success' => true,
            'message' => 'Serviceable',
            'couriers' => [[
                'courier_id' => '1',
                'courier_name' => 'Fake Express',
                'rate' => $rate,
                'etd' => $etd,
                'etd_text' => "{$etd} days",
                'cod' => $codAmount > 0,
            ]],
            'cheapest' => [
                'courier_id' => '1',
                'courier_name' => 'Fake Express',
                'rate' => $rate,
                'etd' => $etd,
            ],
        ];
    }

    public function createOrder(Order $order): Shipment
    {
        $shipment = Shipment::create([
            'order_id' => $order->id,
            'provider' => 'shiprocket',
            'provider_order_id' => 'SR-FAKE-'.Str::upper(Str::random(8)),
            'shipment_id' => (string) random_int(100000, 999999),
            'status' => 'created',
            'freight_charge' => $order->shipping_amount,
            'etd_days' => $order->estimated_delivery_days,
            'request_payload' => ['mode' => 'fake'],
            'response_payload' => ['ok' => true],
        ]);

        $this->log($shipment, 'create_order', 'created', 'Fake Shiprocket order created.');

        return $shipment;
    }

    public function assignAwb(Shipment $shipment, ?string $courierId = null): Shipment
    {
        $shipment->update([
            'courier_id' => $courierId ?: '1',
            'courier_name' => 'Fake Express',
            'awb_code' => 'FAWB'.random_int(10000000, 99999999),
            'status' => 'awb_assigned',
            'label_url' => 'https://example.test/label/'.$shipment->id.'.pdf',
        ]);
        $this->log($shipment, 'assign_awb', 'awb_assigned', 'AWB assigned.');

        return $shipment->fresh();
    }

    public function generatePickup(Shipment $shipment): Shipment
    {
        $shipment->update([
            'status' => 'pickup_scheduled',
            'picked_up_at' => now(),
            'manifest_url' => 'https://example.test/manifest/'.$shipment->id.'.pdf',
            'invoice_url' => 'https://example.test/invoice/'.$shipment->id.'.pdf',
        ]);
        $shipment->order->update(['status' => 'shipped', 'courier_name' => $shipment->courier_name]);
        $this->log($shipment, 'generate_pickup', 'pickup_scheduled', 'Pickup generated.');

        return $shipment->fresh('order');
    }

    public function track(Shipment $shipment): array
    {
        $data = [
            'awb' => $shipment->awb_code,
            'status' => $shipment->status,
            'checkpoints' => [
                ['time' => now()->subDay()->toDateTimeString(), 'status' => 'Picked Up'],
                ['time' => now()->toDateTimeString(), 'status' => 'In Transit'],
            ],
        ];
        $shipment->update(['tracking_data' => $data]);

        return $data;
    }

    public function cancel(Shipment $shipment): Shipment
    {
        $shipment->update(['status' => 'cancelled']);
        $this->log($shipment, 'cancel', 'cancelled', 'Shipment cancelled.');

        return $shipment->fresh();
    }

    public function createReturn(Shipment $shipment): Shipment
    {
        $shipment->update([
            'status' => 'return_initiated',
            'response_payload' => ['return_id' => 'RET-FAKE-'.$shipment->id],
        ]);
        $this->log($shipment, 'create_return', 'return_initiated', 'Return shipment initiated.');

        return $shipment->fresh();
    }

    public function pickupLocations(): array
    {
        return [[
            'id' => 1,
            'pickup_location' => 'Primary Warehouse',
            'pin_code' => '110001',
            'city' => 'New Delhi',
            'state' => 'Delhi',
        ]];
    }

    private function log(Shipment $shipment, string $action, string $status, string $message): void
    {
        ShipmentLog::create([
            'shipment_id' => $shipment->id,
            'order_id' => $shipment->order_id,
            'action' => $action,
            'status' => $status,
            'message' => $message,
            'payload' => ['mode' => 'fake'],
        ]);
    }
}
