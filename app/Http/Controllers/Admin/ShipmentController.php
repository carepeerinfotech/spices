<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Shipment;
use App\Services\Mail\TemplateMailer;
use App\Services\Shipping\ShippingManager;
use Illuminate\Http\Request;

class ShipmentController extends Controller
{
    public function __construct(
        private ShippingManager $shipping,
        private TemplateMailer $mailer,
    ) {}

    public function sendToShiprocket(Order $order)
    {
        if ($order->shipment) {
            return response()->json(['success' => false, 'message' => 'Shipment already exists.'], 422);
        }

        try {
            $shipment = $this->shipping->driver()->createOrder($order->load('items'));

            return response()->json([
                'success' => true,
                'message' => 'Order sent to Shiprocket.',
                'shipment' => $shipment,
            ]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function assignAwb(Request $request, Shipment $shipment)
    {
        $data = $request->validate([
            'courier_id' => ['nullable', 'string'],
        ]);

        try {
            $shipment = $this->shipping->driver()->assignAwb($shipment, $data['courier_id'] ?? null);

            return response()->json(['success' => true, 'message' => 'AWB assigned.', 'shipment' => $shipment]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function pickup(Shipment $shipment)
    {
        try {
            $shipment = $this->shipping->driver()->generatePickup($shipment);
            $this->mailer->send('shipment_update', $shipment->order->customer_email, [
                'customer_name' => $shipment->order->customer_name,
                'order_number' => $shipment->order->order_number,
                'awb' => $shipment->awb_code,
                'status' => $shipment->status,
                'courier' => $shipment->courier_name,
            ]);

            return response()->json(['success' => true, 'message' => 'Pickup generated.', 'shipment' => $shipment]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function track(Shipment $shipment)
    {
        try {
            $tracking = $this->shipping->driver()->track($shipment);

            return response()->json(['success' => true, 'tracking' => $tracking, 'shipment' => $shipment->fresh()]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function cancel(Shipment $shipment)
    {
        try {
            $shipment = $this->shipping->driver()->cancel($shipment);

            return response()->json(['success' => true, 'message' => 'Shipment cancelled.', 'shipment' => $shipment]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function createReturn(Shipment $shipment)
    {
        try {
            $shipment = $this->shipping->driver()->createReturn($shipment);

            return response()->json(['success' => true, 'message' => 'Return initiated.', 'shipment' => $shipment]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }
}
