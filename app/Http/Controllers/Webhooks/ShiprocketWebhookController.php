<?php

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Shipment;
use App\Models\ShipmentLog;
use App\Services\Settings\SettingsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Receives Shiprocket's "Real Time" order/shipment status webhook and mirrors
 * shipped/delivered updates onto our own order status, so status here doesn't
 * depend on an admin manually clicking Track. The exact payload field names
 * Shiprocket sends aren't pinned down from their docs (JS-rendered, no public
 * sample), so every lookup and status match below is tried against several
 * plausible keys rather than one — and the raw payload is always kept on the
 * shipment log so a mismatch is visible instead of silently dropped.
 */
class ShiprocketWebhookController extends Controller
{
    public function __construct(private SettingsService $settings) {}

    public function handle(Request $request, string $token)
    {
        $expected = (string) $this->settings->get('shiprocket', 'webhook_token', '');

        if ($expected === '' || ! hash_equals($expected, $token)) {
            abort(403);
        }

        $payload = $request->all();
        $shipment = $this->resolveShipment($payload);

        if (! $shipment) {
            Log::warning('Shiprocket webhook: no matching shipment.', ['payload' => $payload]);

            return response()->json(['success' => false, 'message' => 'No matching shipment.']);
        }

        $statusText = (string) ($payload['current_status'] ?? $payload['shipment_status'] ?? $payload['status'] ?? '');
        $classified = $statusText !== '' ? $this->classifyStatus($statusText) : null;

        $shipment->update(['tracking_data' => $payload]);

        $order = $shipment->order;

        if ($classified === 'delivered' && $order->status !== 'cancelled') {
            $shipment->update(['status' => 'delivered', 'delivered_at' => now()]);
            $order->update(['status' => 'delivered']);
        } elseif ($classified === 'shipped' && ! in_array($order->status, ['shipped', 'delivered', 'cancelled'], true)) {
            $order->update(['status' => 'shipped']);
        }

        ShipmentLog::create([
            'shipment_id' => $shipment->id,
            'order_id' => $shipment->order_id,
            'action' => 'webhook',
            'status' => $classified ?? ($statusText ?: 'update'),
            'message' => $statusText !== '' ? 'Shiprocket webhook: '.$statusText : 'Shiprocket webhook received.',
            'payload' => $payload,
        ]);

        return response()->json(['success' => true]);
    }

    /**
     * Shiprocket identifies the shipment by AWB, its own order/shipment id, or
     * our channel order number depending on payload version — try the ones
     * most likely to be unique and present, in that order.
     */
    private function resolveShipment(array $payload): ?Shipment
    {
        $awb = $payload['awb'] ?? $payload['awb_code'] ?? null;
        if ($awb && $shipment = Shipment::where('awb_code', $awb)->first()) {
            return $shipment;
        }

        $providerOrderId = $payload['sr_order_id'] ?? null;
        if ($providerOrderId && $shipment = Shipment::where('provider_order_id', $providerOrderId)->first()) {
            return $shipment;
        }

        $shipmentId = $payload['shipment_id'] ?? null;
        if ($shipmentId && $shipment = Shipment::where('shipment_id', $shipmentId)->first()) {
            return $shipment;
        }

        $orderRef = $payload['channel_order_id'] ?? $payload['order_id'] ?? null;
        if ($orderRef) {
            if ($order = Order::where('order_number', $orderRef)->first()) {
                return $order->shipment;
            }

            if ($shipment = Shipment::where('provider_order_id', $orderRef)->first()) {
                return $shipment;
            }
        }

        return null;
    }

    /**
     * Only "shipped" and "delivered" drive an order status change; anything
     * else (RTO, delivery failed, cancelled, ...) is still logged above but
     * left alone here rather than guessed at.
     */
    private function classifyStatus(string $text): ?string
    {
        $normalized = strtolower($text);

        if (str_contains($normalized, 'undeliver') || str_contains($normalized, 'delivery failed')
            || str_contains($normalized, 'rto') || str_contains($normalized, 'return')) {
            return null;
        }

        if (str_contains($normalized, 'delivered')) {
            return 'delivered';
        }

        if (str_contains($normalized, 'shipped') || str_contains($normalized, 'out for delivery')
            || str_contains($normalized, 'in transit') || str_contains($normalized, 'picked up')
            || str_contains($normalized, 'dispatched')) {
            return 'shipped';
        }

        return null;
    }
}
