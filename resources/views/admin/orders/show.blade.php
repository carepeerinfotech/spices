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

        <div class="rounded-xl bg-white border border-slate-200 overflow-hidden">
            <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between gap-3">
                <span class="font-medium">Shiprocket fulfillment</span>
                @if($order->shipment)
                    @php
                        $shipmentStatusMeta = [
                            'created' => ['Created', 'bg-slate-100 text-slate-600'],
                            'awb_assigned' => ['AWB assigned', 'bg-blue-50 text-blue-700'],
                            'pickup_scheduled' => ['Pickup scheduled', 'bg-indigo-50 text-indigo-700'],
                            'delivered' => ['Delivered', 'bg-emerald-50 text-emerald-700'],
                            'cancelled' => ['Cancelled', 'bg-rose-50 text-rose-700'],
                            'return_initiated' => ['Return initiated', 'bg-amber-50 text-amber-700'],
                        ];
                        [$shipmentStatusLabel, $shipmentStatusClass] = $shipmentStatusMeta[$order->shipment->status]
                            ?? [ucwords(str_replace('_', ' ', $order->shipment->status)), 'bg-slate-100 text-slate-600'];
                    @endphp
                    <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-medium {{ $shipmentStatusClass }}">{{ $shipmentStatusLabel }}</span>
                @endif
            </div>

            <div class="p-5 space-y-5">
            @if($order->shipment)
                @if($order->shipment->last_error)
                    <div class="rounded-lg bg-rose-50 border border-rose-200 text-rose-700 text-xs px-3 py-2">
                        <p class="font-medium mb-1">Last error</p>
                        <p class="font-mono whitespace-pre-wrap break-all">{{ $order->shipment->last_error }}</p>
                    </div>
                @endif

                <dl class="grid grid-cols-2 sm:grid-cols-4 gap-x-4 gap-y-4 text-sm">
                    <div>
                        <dt class="text-xs text-slate-500">Provider order</dt>
                        <dd class="font-medium mt-0.5">{{ $order->shipment->provider_order_id ?: '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-slate-500">Shipment ID</dt>
                        <dd class="font-medium mt-0.5">{{ $order->shipment->shipment_id ?: '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-slate-500">AWB</dt>
                        <dd class="font-medium font-mono mt-0.5">{{ $order->shipment->awb_code ?: '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-slate-500">Courier</dt>
                        <dd class="font-medium mt-0.5">{{ $order->shipment->courier_name ?: '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-slate-500">Freight charge</dt>
                        <dd class="font-medium mt-0.5">{{ $order->shipment->freight_charge ? '₹'.number_format($order->shipment->freight_charge, 2) : '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-slate-500">ETD</dt>
                        <dd class="font-medium mt-0.5">{{ $order->shipment->etd_days ? $order->shipment->etd_days.' days' : '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-slate-500">Picked up</dt>
                        <dd class="font-medium mt-0.5">{{ $order->shipment->picked_up_at?->format('d M, g:i A') ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-slate-500">Delivered</dt>
                        <dd class="font-medium mt-0.5">{{ $order->shipment->delivered_at?->format('d M, g:i A') ?? '—' }}</dd>
                    </div>
                </dl>

                @if($order->shipment->label_url || $order->shipment->invoice_url || $order->shipment->manifest_url)
                    <div class="flex flex-wrap gap-2">
                        @if($order->shipment->label_url)
                            <a href="{{ $order->shipment->label_url }}" target="_blank" rel="noopener" class="rounded-lg border border-slate-200 px-2.5 py-1.5 text-xs text-slate-600 hover:border-teal-300 hover:text-teal-700">Shipping label</a>
                        @endif
                        @if($order->shipment->invoice_url)
                            <a href="{{ $order->shipment->invoice_url }}" target="_blank" rel="noopener" class="rounded-lg border border-slate-200 px-2.5 py-1.5 text-xs text-slate-600 hover:border-teal-300 hover:text-teal-700">Invoice</a>
                        @endif
                        @if($order->shipment->manifest_url)
                            <a href="{{ $order->shipment->manifest_url }}" target="_blank" rel="noopener" class="rounded-lg border border-slate-200 px-2.5 py-1.5 text-xs text-slate-600 hover:border-teal-300 hover:text-teal-700">Manifest</a>
                        @endif
                    </div>
                @endif

                <div class="flex flex-wrap gap-2 border-t border-slate-100 pt-4">
                    @if(! $order->shipment->awb_code)
                        <button type="button" class="rounded-lg border border-teal-700 text-teal-700 hover:bg-teal-50 px-3.5 py-2 text-sm font-medium" id="send-shiprocket">Update on Shiprocket</button>
                    @endif
                    @if($order->shipment->awb_code && ! in_array($order->shipment->status, ['pickup_scheduled', 'delivered', 'cancelled', 'return_initiated'], true))
                        <button type="button" class="rounded-lg bg-slate-800 hover:bg-slate-700 text-white px-3.5 py-2 text-sm font-medium" id="generate-pickup">Generate pickup</button>
                    @endif
                    <button type="button" class="rounded-lg border border-slate-300 hover:border-slate-400 px-3.5 py-2 text-sm font-medium" id="track-shipment">Track</button>
                    <button type="button" class="rounded-lg border border-slate-300 hover:border-slate-400 px-3.5 py-2 text-sm font-medium" id="view-order-details">View order details</button>
                    @if(! in_array($order->shipment->status, ['cancelled', 'delivered'], true))
                        <button type="button" class="rounded-lg border border-rose-300 text-rose-700 hover:bg-rose-50 px-3.5 py-2 text-sm font-medium" id="cancel-shipment">Cancel</button>
                    @endif
                    @if(! in_array($order->shipment->status, ['cancelled', 'return_initiated'], true))
                        <button type="button" class="rounded-lg border border-slate-300 hover:border-slate-400 px-3.5 py-2 text-sm font-medium" id="return-shipment">Initiate return</button>
                    @endif
                </div>

                <div id="track-panel" class="hidden border-t border-slate-100 pt-4 space-y-3">
                    <p class="text-sm font-medium">Tracking</p>
                    <ol id="track-timeline" class="space-y-3"></ol>
                    <p id="track-empty" class="hidden text-sm text-slate-500">No tracking checkpoints yet.</p>
                    <details>
                        <summary class="text-xs text-slate-500 cursor-pointer">Raw response</summary>
                        <pre id="track-raw" class="text-xs bg-slate-50 p-3 rounded mt-2 overflow-auto"></pre>
                    </details>
                </div>

                <div id="order-details-box" class="hidden border-t border-slate-100 pt-4 space-y-4">
                    <p class="text-sm font-medium">Order on Shiprocket</p>
                    <dl id="order-details-grid" class="grid grid-cols-2 sm:grid-cols-3 gap-x-4 gap-y-4 text-sm"></dl>
                    <div id="order-details-products-wrap" class="hidden">
                        <p class="text-xs font-medium text-slate-500 mb-2">Products</p>
                        <div class="rounded-lg border border-slate-200 overflow-hidden">
                            <table class="min-w-full text-sm">
                                <thead class="bg-slate-50 text-slate-500 text-left">
                                <tr>
                                    <th class="px-3 py-2 font-medium">Name</th>
                                    <th class="px-3 py-2 font-medium">SKU</th>
                                    <th class="px-3 py-2 font-medium">Units</th>
                                    <th class="px-3 py-2 font-medium">Price</th>
                                </tr>
                                </thead>
                                <tbody id="order-details-products" class="divide-y divide-slate-100"></tbody>
                            </table>
                        </div>
                    </div>
                    <details>
                        <summary class="text-xs text-slate-500 cursor-pointer">Raw response</summary>
                        <pre id="order-details-raw" class="text-xs bg-slate-50 p-3 rounded mt-2 overflow-auto"></pre>
                    </details>
                </div>
            @else
                <p class="text-sm text-slate-500">No shipment yet. Manual send required.</p>
                <button type="button" id="send-shiprocket" class="rounded-lg bg-teal-700 hover:bg-teal-600 text-white px-3.5 py-2 text-sm font-medium">Send to Shiprocket</button>
            @endif
            </div>
        </div>
    </div>

    <div class="space-y-6">
        <div class="rounded-xl bg-white border border-slate-200 p-5 space-y-3 text-sm">
            <div class="flex items-center justify-between">
                <h3 class="font-medium">Customer</h3>
                <button type="button" id="toggle-address-edit" class="text-xs text-teal-700 hover:underline">Edit address</button>
            </div>
            <p class="text-slate-600">{{ $order->customer_email }}</p>

            <div id="address-view">
                <p>{{ $order->customer_name }}</p>
                <p class="text-slate-600">{{ $order->customer_phone }}</p>
                <div class="pt-3 border-t border-slate-100">
                    <p class="font-medium mb-1">Shipping</p>
                    <p class="text-slate-600 whitespace-pre-line">{{ $order->shipping_address }}
{{ $order->shipping_city }}, {{ $order->shipping_state }} {{ $order->shipping_postal_code }}
{{ config('countries.'.$order->shipping_country, $order->shipping_country) }}</p>
                </div>
            </div>

            <form id="address-form" data-ajax method="PATCH" action="{{ route('admin.orders.address', $order) }}" class="hidden space-y-2 pt-1">
                <div>
                    <label class="block text-xs text-slate-500 mb-1">Name</label>
                    <input type="text" name="customer_name" value="{{ $order->customer_name }}" required class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-xs text-slate-500 mb-1">Phone</label>
                    <input type="text" name="customer_phone" value="{{ $order->customer_phone }}" required class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                </div>
                <div class="pt-2 border-t border-slate-100">
                    <label class="block text-xs text-slate-500 mb-1">Address</label>
                    <textarea name="shipping_address" required rows="2" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">{{ $order->shipping_address }}</textarea>
                </div>
                <div class="grid grid-cols-2 gap-2">
                    <div>
                        <label class="block text-xs text-slate-500 mb-1">City</label>
                        <input type="text" name="shipping_city" value="{{ $order->shipping_city }}" required class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                    </div>
                    <div>
                        <label class="block text-xs text-slate-500 mb-1">State</label>
                        <input type="text" name="shipping_state" value="{{ $order->shipping_state }}" required class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                    </div>
                    <div>
                        <label class="block text-xs text-slate-500 mb-1">Postal code</label>
                        <input type="text" name="shipping_postal_code" value="{{ $order->shipping_postal_code }}" required pattern="\d{6}" maxlength="6" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                    </div>
                    <div>
                        <label class="block text-xs text-slate-500 mb-1">Country</label>
                        <select name="shipping_country" required class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                            @foreach(config('countries') as $code => $name)
                                <option value="{{ $code }}" @selected($order->shipping_country === $code)>{{ $name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="flex gap-2 pt-1">
                    <button type="submit" class="rounded-lg bg-teal-700 hover:bg-teal-600 text-white px-3 py-2 text-sm">Save address</button>
                    <button type="button" id="cancel-address-edit" class="rounded-lg border px-3 py-2 text-sm">Cancel</button>
                </div>
            </form>

            <p>Payment: {{ strtoupper($order->payment_method) }} / {{ $order->payment_status }}</p>
        </div>

        <div class="rounded-xl bg-white border border-slate-200 p-5 space-y-2 text-sm">
            @if($order->offersDiscount() > 0)
                <div class="flex justify-between text-slate-500"><span>Items before offers</span><span>₹{{ number_format($order->subtotal + $order->offersDiscount(), 2) }}</span></div>
                <div class="flex justify-between text-emerald-700"><span>Offer savings</span><span>−₹{{ number_format($order->offersDiscount(), 2) }}</span></div>
            @endif
            <div class="flex justify-between"><span>Subtotal</span><span>₹{{ number_format($order->subtotal, 2) }}</span></div>
            @if($order->coupon_discount > 0)
                <div class="flex justify-between text-emerald-700"><span>Coupon ({{ $order->coupon_code }})</span><span>−₹{{ number_format($order->coupon_discount, 2) }}</span></div>
            @endif
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
document.getElementById('toggle-address-edit')?.addEventListener('click', function () {
  document.getElementById('address-view').classList.toggle('hidden');
  document.getElementById('address-form').classList.toggle('hidden');
});
document.getElementById('cancel-address-edit')?.addEventListener('click', function () {
  document.getElementById('address-view').classList.remove('hidden');
  document.getElementById('address-form').classList.add('hidden');
});
document.getElementById('address-form')?.addEventListener('ajax:done', function (e) {
  if (e.detail.ok) location.reload();
});
document.getElementById('send-shiprocket')?.addEventListener('click', function () {
  AppAjax.request(@json(route('admin.orders.shiprocket', $order)), { method: 'POST', body: {} }).then(function (r) {
    if (r.ok) location.reload();
  });
});
@if($order->shipment)
document.getElementById('generate-pickup')?.addEventListener('click', function () {
  AppAjax.request(@json(route('admin.shipments.pickup', $order->shipment)), { method: 'POST', body: {} }).then(function (r) { if (r.ok) location.reload(); });
});

function formatFieldLabel(key) {
  return key.replace(/_/g, ' ').replace(/\b\w/g, function (c) { return c.toUpperCase(); });
}

function statusBadgeClass(text) {
  var t = String(text || '').toLowerCase();
  if (t.indexOf('cancel') !== -1) return 'bg-rose-50 text-rose-700';
  if (t.indexOf('deliver') !== -1) return 'bg-emerald-50 text-emerald-700';
  if (t.indexOf('transit') !== -1 || t.indexOf('pickup') !== -1 || t.indexOf('ship') !== -1) return 'bg-indigo-50 text-indigo-700';
  if (t.indexOf('new') !== -1 || t.indexOf('ready') !== -1) return 'bg-blue-50 text-blue-700';
  return 'bg-slate-100 text-slate-600';
}

function withButtonLoading(btn, promise) {
  var originalText = btn.textContent;
  btn.disabled = true;
  btn.textContent = 'Loading...';
  return promise.finally(function () {
    btn.disabled = false;
    btn.textContent = originalText;
  });
}

document.getElementById('track-shipment')?.addEventListener('click', function () {
  var request = AppAjax.request(@json(route('admin.shipments.track', $order->shipment)), { method: 'GET', toast: false }).then(function (r) {
    if (!r.ok) return;
    var tracking = r.data.tracking || {};
    var timeline = document.getElementById('track-timeline');
    var empty = document.getElementById('track-empty');
    var raw = document.getElementById('track-raw');

    var checkpoints = (tracking.checkpoints
      || tracking.shipment_track_activities
      || (tracking.tracking_data && tracking.tracking_data.shipment_track_activities)
      || []).map(function (cp) {
        return {
          time: cp.time || cp.date || cp['sr-status-date'] || '',
          status: cp.status || cp.activity || cp['sr-status-label'] || 'Update',
          location: cp.location || '',
        };
      });

    timeline.innerHTML = '';
    empty.classList.toggle('hidden', checkpoints.length > 0);

    checkpoints.forEach(function (cp) {
      var li = document.createElement('li');
      li.className = 'flex gap-3';
      var dot = document.createElement('span');
      dot.className = 'mt-1.5 h-2 w-2 shrink-0 rounded-full bg-teal-600';
      var body = document.createElement('div');
      var status = document.createElement('p');
      status.className = 'text-sm font-medium';
      status.textContent = cp.status;
      var meta = document.createElement('p');
      meta.className = 'text-xs text-slate-500';
      meta.textContent = [cp.time, cp.location].filter(Boolean).join(' · ') || '—';
      body.appendChild(status);
      body.appendChild(meta);
      li.appendChild(dot);
      li.appendChild(body);
      timeline.appendChild(li);
    });

    raw.textContent = JSON.stringify(tracking, null, 2);
    document.getElementById('track-panel').classList.remove('hidden');
  });

  withButtonLoading(this, request);
});

document.getElementById('view-order-details')?.addEventListener('click', function () {
  var request = AppAjax.request(@json(route('admin.shipments.details', $order->shipment)), { method: 'GET', toast: false }).then(function (r) {
    if (!r.ok) return;
    var details = r.data.details || {};
    var grid = document.getElementById('order-details-grid');
    var productsWrap = document.getElementById('order-details-products-wrap');
    var productsBody = document.getElementById('order-details-products');
    var raw = document.getElementById('order-details-raw');

    grid.innerHTML = '';
    productsBody.innerHTML = '';

    Object.keys(details).forEach(function (key) {
      if (key === 'products' || key === 'shipments') return;
      var value = details[key];
      if (value !== null && typeof value === 'object') return;

      var wrap = document.createElement('div');
      var dt = document.createElement('dt');
      dt.className = 'text-xs text-slate-500';
      dt.textContent = formatFieldLabel(key);
      var dd = document.createElement('dd');
      dd.className = 'font-medium mt-0.5';

      if (key === 'status' && value) {
        var pill = document.createElement('span');
        pill.className = 'inline-flex rounded-full px-2 py-0.5 text-xs font-medium ' + statusBadgeClass(value);
        pill.textContent = value;
        dd.appendChild(pill);
      } else {
        dd.textContent = (value === null || value === '') ? '—' : String(value);
      }

      wrap.appendChild(dt);
      wrap.appendChild(dd);
      grid.appendChild(wrap);
    });

    var products = details.products || [];
    productsWrap.classList.toggle('hidden', products.length === 0);
    products.forEach(function (product) {
      var row = document.createElement('tr');
      ['name', 'sku', 'units', 'selling_price'].forEach(function (field) {
        var cell = document.createElement('td');
        cell.className = 'px-3 py-2';
        cell.textContent = product[field] ?? '—';
        row.appendChild(cell);
      });
      productsBody.appendChild(row);
    });

    raw.textContent = JSON.stringify(details, null, 2);
    document.getElementById('order-details-box').classList.remove('hidden');
  });

  withButtonLoading(this, request);
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
