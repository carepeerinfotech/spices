@extends('admin.layouts.app')

@section('title', 'Order '.$order->order_number)
@section('heading', 'Order '.$order->order_number)
@section('subtitle', 'Placed '.$order->created_at->format('M j, Y g:i A'))

@section('content')
<div class="grid lg:grid-cols-3 gap-6">
    <div class="lg:col-span-2 space-y-6">
        <div class="rounded-xl bg-white border border-slate-200 overflow-hidden">
            <div class="px-5 py-4 border-b border-slate-100 font-medium">Items</div>
            <table class="min-w-full text-sm">
                <thead class="bg-slate-50 text-slate-500 text-left">
                <tr>
                    <th class="px-5 py-3 font-medium">Product</th>
                    <th class="px-5 py-3 font-medium">Qty</th>
                    <th class="px-5 py-3 font-medium">Price</th>
                    <th class="px-5 py-3 font-medium">Total</th>
                </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                @foreach($order->items as $item)
                    <tr>
                        <td class="px-5 py-3">
                            <div class="font-medium">{{ $item->product_name }}</div>
                            <div class="text-xs text-slate-500">{{ $item->product_sku }} @if($item->variant_label) · {{ $item->variant_label }} @endif</div>
                            @if($item->offer)
                                <span class="inline-block mt-1 text-xs rounded bg-teal-50 text-teal-800 border border-teal-200 px-1.5 py-0.5">
                                    {{ $item->offer->label() }} applied · −₹{{ number_format($item->offer->discount_amount, 2) }}
                                </span>
                            @endif
                        </td>
                        <td class="px-5 py-3">{{ $item->quantity }}</td>
                        <td class="px-5 py-3">
                            @if($item->offer && $item->offer->unit_discount > 0)
                                <span class="text-xs text-slate-400 line-through mr-1">₹{{ number_format($item->price + $item->offer->unit_discount, 2) }}</span>
                            @endif
                            ₹{{ number_format($item->price, 2) }}
                        </td>
                        <td class="px-5 py-3">₹{{ number_format($item->total, 2) }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>

        @if($order->offers->isNotEmpty())
            <div class="rounded-xl bg-white border border-slate-200 overflow-hidden">
                <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
                    <span class="font-medium">Offers applied</span>
                    <span class="text-xs text-slate-500">Recorded when the order was placed</span>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="bg-slate-50 text-slate-500 text-left">
                        <tr>
                            <th class="px-5 py-3 font-medium">Product</th>
                            <th class="px-5 py-3 font-medium">Offer</th>
                            <th class="px-5 py-3 font-medium">Discount</th>
                            <th class="px-5 py-3 font-medium">Per unit</th>
                            <th class="px-5 py-3 font-medium">Valid</th>
                        </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                        @foreach($order->offers as $offer)
                            <tr>
                                <td class="px-5 py-3">{{ $offer->orderItem?->product_name ?? '—' }}</td>
                                <td class="px-5 py-3">
                                    <div>{{ $offer->name ?: '—' }}</div>
                                    <div class="text-xs text-slate-500">
                                        {{ $offer->discount_type === 'percentage' ? rtrim(rtrim($offer->value, '0'), '.').'%' : '₹'.number_format($offer->value, 2) }}
                                        {{ $offer->discount_type === 'percentage' ? 'of line price' : 'flat' }}
                                    </div>
                                </td>
                                <td class="px-5 py-3">−₹{{ number_format($offer->discount_amount, 2) }}</td>
                                <td class="px-5 py-3 text-slate-500">−₹{{ number_format($offer->unit_discount, 2) }}</td>
                                <td class="px-5 py-3 text-xs text-slate-500">
                                    {{ \App\Support\LocalTime::display($offer->starts_at, 'd M Y H:i') ?? '—' }}<br>
                                    to {{ \App\Support\LocalTime::display($offer->ends_at, 'd M Y H:i') ?? '—' }}
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                        <tfoot>
                        <tr class="bg-slate-50">
                            <td class="px-5 py-3 font-medium" colspan="2">Total recorded discount</td>
                            <td class="px-5 py-3 font-medium" colspan="3">−₹{{ number_format($order->offersDiscount(), 2) }}</td>
                        </tr>
                        </tfoot>
                    </table>
                </div>
                <p class="px-5 py-3 text-xs text-slate-500 bg-slate-50 border-t border-slate-100">
                    Already deducted — the item prices and order totals shown are after these offers.
                </p>
            </div>
        @endif

        <div class="rounded-xl bg-white border border-slate-200 p-5 space-y-3">
            <h3 class="font-medium">Shiprocket fulfillment</h3>
            @if($order->shipment)
                <p class="text-sm">Status: <strong>{{ $order->shipment->status }}</strong></p>
                <p class="text-sm">Provider order: {{ $order->shipment->provider_order_id }}</p>
                <p class="text-sm">AWB: {{ $order->shipment->awb_code ?: '—' }}</p>
                <p class="text-sm">Courier: {{ $order->shipment->courier_name ?: '—' }}</p>
                <div class="flex flex-wrap gap-2">
                    @if(! $order->shipment->awb_code)
                        <button type="button" class="rounded-lg bg-teal-700 text-white px-3 py-2 text-sm" id="assign-awb">Assign AWB</button>
                    @endif
                    @if($order->shipment->awb_code && $order->shipment->status !== 'pickup_scheduled')
                        <button type="button" class="rounded-lg bg-slate-800 text-white px-3 py-2 text-sm" id="generate-pickup">Generate pickup</button>
                    @endif
                    <button type="button" class="rounded-lg border px-3 py-2 text-sm" id="track-shipment">Track</button>
                    <button type="button" class="rounded-lg border border-rose-300 text-rose-700 px-3 py-2 text-sm" id="cancel-shipment">Cancel</button>
                    <button type="button" class="rounded-lg border px-3 py-2 text-sm" id="return-shipment">Initiate return</button>
                </div>
                <pre id="track-box" class="text-xs bg-slate-50 p-3 rounded hidden overflow-auto"></pre>
            @else
                <p class="text-sm text-slate-500">No shipment yet. Manual send required.</p>
                <button type="button" id="send-shiprocket" class="rounded-lg bg-teal-700 text-white px-3 py-2 text-sm">Send to Shiprocket</button>
            @endif
        </div>
    </div>

    <div class="space-y-6">
        <div class="rounded-xl bg-white border border-slate-200 p-5 space-y-3 text-sm">
            <h3 class="font-medium">Customer</h3>
            <p>{{ $order->customer_name }}</p>
            <p class="text-slate-600">{{ $order->customer_email }}</p>
            <p class="text-slate-600">{{ $order->customer_phone }}</p>
            <div class="pt-3 border-t border-slate-100">
                <p class="font-medium mb-1">Shipping</p>
                <p class="text-slate-600 whitespace-pre-line">{{ $order->shipping_address }}
{{ $order->shipping_city }}, {{ $order->shipping_state }} {{ $order->shipping_postal_code }}
{{ $order->shipping_country }}</p>
            </div>
            <p>Payment: {{ strtoupper($order->payment_method) }} / {{ $order->payment_status }}</p>
        </div>

        <div class="rounded-xl bg-white border border-slate-200 p-5 space-y-2 text-sm">
            @if($order->offersDiscount() > 0)
                <div class="flex justify-between text-slate-500"><span>Items before offers</span><span>₹{{ number_format($order->subtotal + $order->offersDiscount(), 2) }}</span></div>
                <div class="flex justify-between text-emerald-700"><span>Offer savings</span><span>−₹{{ number_format($order->offersDiscount(), 2) }}</span></div>
            @endif
            <div class="flex justify-between"><span>Subtotal</span><span>₹{{ number_format($order->subtotal, 2) }}</span></div>
            <div class="flex justify-between"><span>Shipping</span><span>₹{{ number_format($order->shipping_amount, 2) }}</span></div>
            <div class="flex justify-between"><span>GST</span><span>₹{{ number_format($order->tax_amount, 2) }}</span></div>
            <div class="flex justify-between font-semibold text-base pt-2 border-t border-slate-100"><span>Total</span><span>₹{{ number_format($order->total, 2) }}</span></div>
        </div>

        <div class="rounded-xl bg-white border border-slate-200 p-5">
            <label class="block text-sm font-medium mb-2">Update status</label>
            <select id="order-status" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm mb-3">
                @foreach(['pending','processing','shipped','delivered','cancelled'] as $status)
                    <option value="{{ $status }}" @selected($order->status === $status)>{{ ucfirst($status) }}</option>
                @endforeach
            </select>
            <button type="button" id="save-status" class="w-full rounded-lg bg-teal-700 hover:bg-teal-600 text-white px-4 py-2 text-sm">Save status</button>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.getElementById('save-status')?.addEventListener('click', function () {
  AppAjax.request(@json(route('admin.orders.status', $order)), {
    method: 'PATCH',
    body: { status: document.getElementById('order-status').value }
  });
});
document.getElementById('send-shiprocket')?.addEventListener('click', function () {
  AppAjax.request(@json(route('admin.orders.shiprocket', $order)), { method: 'POST', body: {} }).then(function (r) {
    if (r.ok) location.reload();
  });
});
@if($order->shipment)
document.getElementById('assign-awb')?.addEventListener('click', function () {
  AppAjax.request(@json(route('admin.shipments.awb', $order->shipment)), { method: 'POST', body: {} }).then(function (r) { if (r.ok) location.reload(); });
});
document.getElementById('generate-pickup')?.addEventListener('click', function () {
  AppAjax.request(@json(route('admin.shipments.pickup', $order->shipment)), { method: 'POST', body: {} }).then(function (r) { if (r.ok) location.reload(); });
});
document.getElementById('track-shipment')?.addEventListener('click', function () {
  AppAjax.request(@json(route('admin.shipments.track', $order->shipment)), { method: 'GET', toast: false }).then(function (r) {
    var box = document.getElementById('track-box');
    box.classList.remove('hidden');
    box.textContent = JSON.stringify(r.data.tracking || r.data, null, 2);
  });
});
document.getElementById('cancel-shipment')?.addEventListener('click', function () {
  if (!confirm('Cancel shipment?')) return;
  AppAjax.request(@json(route('admin.shipments.cancel', $order->shipment)), { method: 'POST', body: {} }).then(function (r) { if (r.ok) location.reload(); });
});
document.getElementById('return-shipment')?.addEventListener('click', function () {
  if (!confirm('Initiate return shipment?')) return;
  AppAjax.request(@json(route('admin.shipments.return', $order->shipment)), { method: 'POST', body: {} }).then(function (r) { if (r.ok) location.reload(); });
});
@endif
</script>
@endpush
