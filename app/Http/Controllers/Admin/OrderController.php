<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $orders = Order::withCount('items')
            ->when($request->status, fn ($q) => $q->where('status', $request->status))
            ->when($request->q, fn ($q) => $q->where(function ($query) use ($request) {
                $query->where('order_number', 'like', '%'.$request->q.'%')
                    ->orWhere('customer_email', 'like', '%'.$request->q.'%')
                    ->orWhere('customer_name', 'like', '%'.$request->q.'%');
            }))
            ->latest()
            ->paginate(15);

        return view('admin.orders.index', compact('orders'));
    }

    public function show(Order $order)
    {
        $order->load(['items.product', 'items.variant', 'items.offer', 'offers.orderItem', 'shipment.logs', 'payments']);

        return view('admin.orders.show', compact('order'));
    }

    public function updateStatus(Request $request, Order $order)
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(['pending', 'processing', 'shipped', 'delivered', 'cancelled'])],
        ]);

        $order->update(['status' => $data['status']]);

        return response()->json([
            'success' => true,
            'message' => 'Order status updated.',
            'status' => $order->status,
        ]);
    }

    public function updateAddress(Request $request, Order $order)
    {
        $data = $request->validate([
            'customer_name' => ['required', 'string', 'max:255'],
            'customer_phone' => ['required', 'string', 'max:20'],
            'shipping_address' => ['required', 'string', 'max:500'],
            'shipping_city' => ['required', 'string', 'max:100'],
            'shipping_state' => ['required', 'string', 'max:100'],
            'shipping_postal_code' => ['required', 'regex:/^\d{6}$/'],
            'shipping_country' => ['required', Rule::in(array_keys(config('countries')))],
        ]);

        $order->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Shipping address updated.',
        ]);
    }
}
