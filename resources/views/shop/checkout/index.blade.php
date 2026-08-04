@extends('shop.layouts.app')

@section('title', 'Checkout — '.config('app.name'))

@section('content')
<div class="max-w-5xl mx-auto px-4 sm:px-6 py-10">
    @if($addresses->isEmpty())
        <div class="rounded-xl border border-amber-200 bg-amber-50 p-5 mb-6 text-sm">
            Please add a shipping address before checkout.
            <a href="{{ route('account.addresses.index') }}" class="text-brand underline ml-1">Manage addresses</a>
        </div>
    @endif

    <div class="grid lg:grid-cols-5 gap-8">
        <form data-ajax method="POST" action="{{ route('shop.checkout.store') }}" class="lg:col-span-3 rounded-xl border border-slate-200 bg-white p-6 space-y-4" id="checkout-form">
            @csrf
            <h2 class="font-medium text-lg">Shipping address</h2>
            <div class="space-y-2">
                @foreach($addresses as $address)
                    <label class="flex gap-3 rounded-lg border border-slate-200 p-3 text-sm">
                        <input type="radio" name="shipping_address_id" value="{{ $address->id }}" @checked(($defaultShipping?->id ?? null) === $address->id) required>
                        <span>
                            <strong>{{ $address->name }}</strong> · {{ $address->phone }}<br>
                            {{ $address->fullAddress() }}
                        </span>
                    </label>
                @endforeach
            </div>

            <label class="inline-flex items-center gap-2 text-sm">
                <input type="checkbox" name="billing_same_as_shipping" value="1" data-bool checked id="billing-same"> Billing same as shipping
            </label>

            <div id="billing-box" class="hidden space-y-2">
                <h2 class="font-medium text-lg">Billing address</h2>
                @foreach($addresses as $address)
                    <label class="flex gap-3 rounded-lg border border-slate-200 p-3 text-sm">
                        <input type="radio" name="billing_address_id" value="{{ $address->id }}" @checked(($defaultBilling?->id ?? null) === $address->id)>
                        <span>{{ $address->name }} — {{ $address->fullAddress() }}</span>
                    </label>
                @endforeach
            </div>

            <div>
                <label class="block text-sm mb-1">Delivery pincode check</label>
                <div class="flex gap-2">
                    <input id="checkout-pincode" type="text" maxlength="6" value="{{ $defaultShipping?->postal_code }}" class="rounded-lg border border-slate-300 px-3 py-2 text-sm w-40">
                    <button type="button" id="quote-shipping" class="rounded-lg border border-slate-300 px-3 py-2 text-sm">Calculate shipping</button>
                </div>
                <p id="quote-result" class="text-sm text-slate-600 mt-2"></p>
                <input type="hidden" name="shipping_rate" id="shipping_rate" value="">
                <input type="hidden" name="courier_name" id="courier_name" value="">
                <input type="hidden" name="estimated_delivery_days" id="estimated_delivery_days" value="">
            </div>

            <div>
                <h2 class="font-medium text-lg mb-2">Payment method</h2>
                <div class="space-y-2 text-sm">
                    @if($codEnabled)
                        <label class="flex gap-2 items-center"><input type="radio" name="payment_method" value="cod" checked> Cash on Delivery</label>
                    @endif
                    @if($onlineEnabled)
                        <label class="flex gap-2 items-center"><input type="radio" name="payment_method" value="paytm" @checked(! $codEnabled)> Paytm (Online)</label>
                    @endif
                    @if(! $codEnabled && ! $onlineEnabled)
                        <p class="text-rose-600">No payment methods are enabled.</p>
                    @endif
                </div>
            </div>

            <div>
                <label class="block text-sm mb-1">Order notes</label>
                <textarea name="notes" rows="2" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"></textarea>
            </div>

            <button type="submit" data-loading="Placing order..." class="rounded-lg bg-brand hover:bg-brand-dark text-white px-5 py-2.5 text-sm font-medium" @disabled($addresses->isEmpty())>
                Place order
            </button>
        </form>

        <div class="lg:col-span-2">
            <div class="rounded-xl border border-slate-200 bg-white p-5 sticky top-24">
                <h2 class="font-medium mb-4">Order summary</h2>
                <div class="space-y-3 text-sm mb-4">
                    @foreach($summary['items'] as $item)
                        <div class="flex justify-between gap-3">
                            <span class="text-slate-600">{{ $item['name'] }} @if($item['variant_label']) ({{ $item['variant_label'] }}) @endif × {{ $item['quantity'] }}</span>
                            <span>₹{{ number_format($item['line_total'], 2) }}</span>
                        </div>
                    @endforeach
                </div>
                <div class="space-y-2 text-sm border-t border-slate-100 pt-4">
                    <div class="flex justify-between"><span>Subtotal</span><span id="sum-subtotal">₹{{ number_format($summary['subtotal'], 2) }}</span></div>
                    <div class="flex justify-between"><span>Shipping</span><span id="sum-shipping">₹{{ number_format($summary['shipping'], 2) }}</span></div>
                    <div class="flex justify-between"><span>GST ({{ $summary['tax_percent'] }}%)</span><span id="sum-tax">₹{{ number_format($summary['tax'], 2) }}</span></div>
                    <div class="flex justify-between font-semibold text-base pt-2"><span>Total</span><span id="sum-total">₹{{ number_format($summary['total'], 2) }}</span></div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.getElementById('billing-same')?.addEventListener('change', function () {
  document.getElementById('billing-box').classList.toggle('hidden', this.checked);
});

document.getElementById('quote-shipping')?.addEventListener('click', function () {
  var pincode = document.getElementById('checkout-pincode').value.trim();
  var cod = document.querySelector('input[name="payment_method"]:checked')?.value === 'cod';
  AppAjax.request(@json(route('shipping.quote.cart')), {
    method: 'POST',
    body: { pincode: pincode, cod: cod ? 1 : 0 },
    toast: false
  }).then(function (result) {
    var el = document.getElementById('quote-result');
    if (!result.ok) {
      el.textContent = result.data.message || 'Unable to quote shipping.';
      el.className = 'text-sm text-rose-600 mt-2';
      return;
    }
    var q = result.data.quote;
    var s = result.data.summary;
    el.textContent = '₹' + Number(q.rate).toFixed(2) + ' via ' + q.courier_name + ' · ETA ' + q.etd + ' days';
    el.className = 'text-sm text-emerald-700 mt-2';
    document.getElementById('shipping_rate').value = q.rate;
    document.getElementById('courier_name').value = q.courier_name;
    document.getElementById('estimated_delivery_days').value = q.etd;
    document.getElementById('sum-subtotal').textContent = '₹' + Number(s.subtotal).toFixed(2);
    document.getElementById('sum-shipping').textContent = '₹' + Number(s.shipping).toFixed(2);
    document.getElementById('sum-tax').textContent = '₹' + Number(s.tax).toFixed(2);
    document.getElementById('sum-total').textContent = '₹' + Number(s.total).toFixed(2);
  });
});
</script>
@endpush
