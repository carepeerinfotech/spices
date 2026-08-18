@extends('shop.layouts.app')

@section('title', 'Cart — '.config('app.name'))

@section('content')
<x-shop.breadcrumb title="Cart" />

<div class="max-w-6xl mx-auto px-4 sm:px-6 py-10">
    <div id="cart-empty" class="{{ $summary['item_count'] ? 'hidden' : '' }}">
        <div class="max-w-md mx-auto text-center py-10 sm:py-16">
            <div class="w-20 h-20 mx-auto rounded-full bg-cream-dark text-brand flex items-center justify-center mb-6">
                <svg width="34" height="34" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                     stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <circle cx="9" cy="20" r="1.5"/>
                    <circle cx="18" cy="20" r="1.5"/>
                    <path d="M2 3h2.2a1 1 0 0 1 .98.8L6 8m0 0 1.55 7.4a1 1 0 0 0 .98.8h9.2a1 1 0 0 0 .97-.75L21 8H6Z"/>
                </svg>
            </div>

            <h2 class="font-display text-2xl sm:text-3xl mb-2">Your cart is empty</h2>
            <p class="text-slate-500 leading-relaxed mb-7">
                Looks like you haven't added anything yet. Explore our range of
                authentic spices and blends to get started.
            </p>

            <a href="{{ route('shop.catalog') }}"
               class="inline-flex items-center justify-center gap-2 rounded-lg bg-brand hover:bg-brand-dark text-white px-6 py-3 text-sm font-medium transition">
                Start shopping
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                     stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M5 12h14M13 6l6 6-6 6"/>
                </svg>
            </a>

            <p class="mt-5 text-sm">
                <a href="{{ route('shop.home') }}" class="text-slate-500 hover:text-brand transition">or return to the homepage</a>
            </p>
        </div>
    </div>

    <div id="cart-content" class="{{ $summary['item_count'] ? '' : 'hidden' }}">
        <div class="lg:flex lg:gap-8 lg:items-start">
            <div class="rounded-xl border border-slate-200 bg-white overflow-hidden mb-6 lg:mb-0 lg:flex-1">
                <div id="cart-items" class="divide-y divide-slate-100">
                    @foreach($summary['items'] as $item)
                        <div class="p-4 sm:p-5 flex flex-wrap sm:flex-nowrap gap-4 items-center" data-item-id="{{ $item['id'] }}">
                            <div class="w-16 h-16 rounded-lg overflow-hidden bg-slate-100 shrink-0">
                                @if($item['image'])
                                    <img src="{{ $item['image'] }}" alt="" class="w-full h-full object-cover">
                                @endif
                            </div>
                            <div class="flex-1 min-w-0">
                                <a href="{{ route('shop.product', $item['slug']) }}" class="font-medium hover:text-brand">{{ $item['name'] }}</a>
                                <p class="text-sm text-slate-500 mt-0.5">
                                    ₹{{ number_format($item['price'], 2) }} each
                                    @if($item['discount'] > 0)
                                        <span class="line-through text-slate-400 ml-1">₹{{ number_format($item['original_price'], 2) }}</span>
                                    @endif
                                </p>
                                @if($item['offer'])
                                    <span class="inline-block mt-1 text-xs text-emerald-800 bg-emerald-50 border border-emerald-200 rounded px-1.5 py-0.5">
                                        @if($item['offer']['name']){{ $item['offer']['name'] }} · @endif{{ $item['offer']['label'] }}
                                    </span>
                                @endif
                            </div>
                            <div class="flex items-center gap-2">
                                <input type="number" min="0" max="{{ $item['stock'] }}" value="{{ $item['quantity'] }}"
                                       class="qty-input w-16 rounded-lg border border-slate-300 px-2 py-1.5 text-sm"
                                       data-item="{{ $item['id'] }}">
                                <button type="button" class="remove-item text-sm text-rose-600 hover:underline" data-item="{{ $item['id'] }}">Remove</button>
                            </div>
                            <div class="w-20 text-right font-medium line-total">₹{{ number_format($item['line_total'], 2) }}</div>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="rounded-xl border border-slate-200 bg-white p-5 space-y-2 text-sm w-full lg:w-80 lg:shrink-0">
                <div id="row-gross" class="flex justify-between text-slate-500 {{ $summary['discount'] > 0 ? '' : 'hidden' }}">
                    <span>Items</span><span id="sum-gross">₹{{ number_format($summary['gross_subtotal'], 2) }}</span>
                </div>
                <div id="row-discount" class="flex justify-between text-emerald-700 {{ $summary['discount'] > 0 ? '' : 'hidden' }}">
                    <span>Offer savings</span><span id="sum-discount">−₹{{ number_format($summary['discount'], 2) }}</span>
                </div>
                <div class="flex justify-between"><span>Subtotal</span><span id="sum-subtotal">₹{{ number_format($summary['subtotal'], 2) }}</span></div>
                <div class="flex justify-between"><span>Shipping</span><span id="sum-shipping">₹{{ number_format($summary['shipping'], 2) }}</span></div>
                <div class="flex justify-between"><span>GST ({{ $summary['tax_percent'] }}%)</span><span id="sum-tax">₹{{ number_format($summary['tax'], 2) }}</span></div>
                <div class="flex justify-between font-semibold text-base pt-2 border-t border-slate-100">
                    <span>Total</span><span id="sum-total">₹{{ number_format($summary['total'], 2) }}</span>
                </div>
                <a href="{{ route('shop.checkout') }}" class="mt-4 inline-flex w-full justify-center rounded-lg bg-brand hover:bg-brand-dark text-white px-4 py-2.5 text-sm font-medium transition">
                    Checkout
                </a>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
    function money(n) { return '₹' + Number(n).toFixed(2); }

    function render(summary) {
        AppAjax.updateCartBadge(summary.item_count);
        document.getElementById('sum-gross').textContent = money(summary.gross_subtotal);
        document.getElementById('sum-discount').textContent = '−' + money(summary.discount);
        document.getElementById('row-gross').classList.toggle('hidden', !(summary.discount > 0));
        document.getElementById('row-discount').classList.toggle('hidden', !(summary.discount > 0));
        document.getElementById('sum-subtotal').textContent = money(summary.subtotal);
        document.getElementById('sum-shipping').textContent = money(summary.shipping);
        document.getElementById('sum-tax').textContent = money(summary.tax);
        document.getElementById('sum-total').textContent = money(summary.total);

        var empty = document.getElementById('cart-empty');
        var content = document.getElementById('cart-content');
        if (!summary.item_count) {
            empty.classList.remove('hidden');
            content.classList.add('hidden');
            return;
        }
        empty.classList.add('hidden');
        content.classList.remove('hidden');

        var root = document.getElementById('cart-items');
        root.innerHTML = summary.items.map(function (item) {
            return '<div class="p-4 sm:p-5 flex flex-wrap sm:flex-nowrap gap-4 items-center" data-item-id="' + item.id + '">' +
                '<div class="w-16 h-16 rounded-lg overflow-hidden bg-slate-100 shrink-0">' +
                (item.image ? '<img src="' + item.image + '" alt="" class="w-full h-full object-cover">' : '') +
                '</div>' +
                '<div class="flex-1 min-w-0"><a href="/product/' + item.slug + '" class="font-medium hover:text-brand">' + item.name + '</a>' +
                '<p class="text-sm text-slate-500 mt-0.5">' + money(item.price) + ' each' +
                (item.discount > 0 ? '<span class="line-through text-slate-400 ml-1">' + money(item.original_price) + '</span>' : '') +
                '</p>' +
                (item.offer ? '<span class="inline-block mt-1 text-xs text-emerald-800 bg-emerald-50 border border-emerald-200 rounded px-1.5 py-0.5">' +
                  (item.offer.name ? item.offer.name + ' · ' : '') + item.offer.label + '</span>' : '') +
                '</div>' +
                '<div class="flex items-center gap-2">' +
                '<input type="number" min="0" max="' + item.stock + '" value="' + item.quantity + '" class="qty-input w-16 rounded-lg border border-slate-300 px-2 py-1.5 text-sm" data-item="' + item.id + '">' +
                '<button type="button" class="remove-item text-sm text-rose-600 hover:underline" data-item="' + item.id + '">Remove</button></div>' +
                '<div class="w-20 text-right font-medium line-total">' + money(item.line_total) + '</div></div>';
        }).join('');
    }

    document.addEventListener('change', function (e) {
        var input = e.target.closest('.qty-input');
        if (!input) return;
        var id = input.getAttribute('data-item');
        var max = parseInt(input.max, 10);
        var qty = parseInt(input.value, 10) || 0;
        if (max > 0 && qty > max) { qty = max; input.value = max; }
        AppAjax.request('/cart/' + id, { method: 'PUT', body: { quantity: qty } }).then(function (r) {
            if (r.ok) render(r.data.data);
        });
    });

    document.addEventListener('click', function (e) {
        var btn = e.target.closest('.remove-item');
        if (!btn) return;
        AppAjax.request('/cart/' + btn.getAttribute('data-item'), { method: 'DELETE' }).then(function (r) {
            if (r.ok) render(r.data.data);
        });
    });
})();
</script>
@endpush
