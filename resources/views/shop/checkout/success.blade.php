@extends('shop.layouts.app')

@section('title', 'Order Confirmed — '.config('app.name'))

@php
$statusColors = [
    'pending' => 'bg-amber-100 text-amber-700',
    'processing' => 'bg-sky-100 text-sky-700',
    'shipped' => 'bg-indigo-100 text-indigo-700',
    'delivered' => 'bg-emerald-100 text-emerald-700',
    'cancelled' => 'bg-rose-100 text-rose-700',
];
$statusClass = $statusColors[$order->status] ?? 'bg-stone-100 text-stone-700';
@endphp

@section('content')
<div class="max-w-4xl mx-auto px-4 sm:px-6 py-10 sm:py-14">
    <x-shop.breadcrumb-trail :items="[['label' => 'Order Confirmed']]" />

    <div class="text-center mb-8 sm:mb-10">
        <div class="w-16 h-16 rounded-full bg-emerald-100 text-emerald-600 flex items-center justify-center mx-auto mb-4">
            <svg width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
        </div>
        <h1 class="font-display text-3xl sm:text-4xl tracking-tight mb-2">Order Confirmed!</h1>
        <p class="text-stone-600 max-w-md mx-auto">Thank you{{ $order->customer_name ? ', '.$order->customer_name : '' }}. We've received your order and sent a confirmation to <span class="font-medium text-stone-800">{{ $order->customer_email }}</span>.</p>
    </div>

    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 sm:gap-6 rounded-2xl border border-[var(--line)] bg-white p-5 sm:p-6 mb-6 sm:mb-8 text-center sm:text-left">
        <div>
            <p class="text-[11px] uppercase tracking-wide text-stone-400 mb-1">Order Number</p>
            <p class="font-mono font-semibold text-brand text-sm sm:text-base break-all">{{ $order->order_number }}</p>
        </div>
        <div>
            <p class="text-[11px] uppercase tracking-wide text-stone-400 mb-1">Date</p>
            <p class="font-medium text-sm sm:text-base">{{ $order->created_at->format('M j, Y') }}</p>
        </div>
        <div>
            <p class="text-[11px] uppercase tracking-wide text-stone-400 mb-1">Payment</p>
            <p class="font-medium text-sm sm:text-base">{{ $order->payment_method === 'cod' ? 'Cash on Delivery' : 'Paytm (Online)' }}</p>
        </div>
        <div>
            <p class="text-[11px] uppercase tracking-wide text-stone-400 mb-1">Status</p>
            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold capitalize {{ $statusClass }}">{{ $order->status }}</span>
        </div>
    </div>

    <div class="grid lg:grid-cols-5 gap-6">
        <div class="lg:col-span-3 rounded-2xl border border-[var(--line)] bg-white overflow-hidden">
            <div class="px-5 sm:px-6 py-4 border-b border-[var(--line)]">
                <h2 class="font-medium">Order Items</h2>
            </div>
            <div class="divide-y divide-[var(--line)]">
                @foreach($order->items as $item)
                    <div class="flex items-center justify-between gap-4 px-5 sm:px-6 py-4">
                        <div class="min-w-0">
                            <p class="font-medium text-sm truncate">{{ $item->product_name }}</p>
                            @if($item->variant_label)
                                <p class="text-xs text-stone-500 mt-0.5">{{ $item->variant_label }}</p>
                            @endif
                            <p class="text-xs text-stone-400 mt-0.5">Qty {{ $item->quantity }} × ₹{{ number_format($item->price, 2) }}</p>
                        </div>
                        <p class="font-medium text-sm shrink-0">₹{{ number_format($item->total, 2) }}</p>
                    </div>
                @endforeach
            </div>
            <div class="px-5 sm:px-6 py-4 border-t border-[var(--line)] space-y-2 text-sm bg-cream/40">
                <div class="flex justify-between"><span class="text-stone-500">Subtotal</span><span>₹{{ number_format($order->subtotal, 2) }}</span></div>
                <div class="flex justify-between"><span class="text-stone-500">Shipping</span><span>₹{{ number_format($order->shipping_amount, 2) }}</span></div>
                <div class="flex justify-between"><span class="text-stone-500">GST ({{ $order->tax_percent }}%)</span><span>₹{{ number_format($order->tax_amount, 2) }}</span></div>
                <div class="flex justify-between text-base font-semibold pt-2 border-t border-[var(--line)]"><span>Total</span><span class="text-brand">₹{{ number_format($order->total, 2) }}</span></div>
            </div>
        </div>

        <div class="lg:col-span-2 space-y-6">
            <div class="rounded-2xl border border-[var(--line)] bg-white p-5 sm:p-6">
                <h2 class="font-medium mb-3">Shipping Address</h2>
                <p class="text-sm text-stone-600 leading-relaxed">
                    {{ $order->shipping_name ?: $order->customer_name }}<br>
                    {{ $order->shipping_address }}<br>
                    {{ $order->shipping_city }}, {{ $order->shipping_state }} {{ $order->shipping_postal_code }}<br>
                    India
                </p>
                @if($showDeliveryDetails && ($order->courier_name || $order->estimated_delivery_days))
                    <div class="mt-4 pt-4 border-t border-[var(--line)] text-sm text-stone-600 space-y-1">
                        @if($order->courier_name)
                            <p>Courier: <span class="font-medium text-stone-800">{{ $order->courier_name }}</span></p>
                        @endif
                        @if($order->estimated_delivery_days)
                            <p>Estimated delivery: <span class="font-medium text-stone-800">{{ $order->estimated_delivery_days }} day(s)</span></p>
                        @endif
                    </div>
                @endif
            </div>

            @if($order->notes)
                <div class="rounded-2xl border border-[var(--line)] bg-white p-5 sm:p-6">
                    <h2 class="font-medium mb-2">Order Notes</h2>
                    <p class="text-sm text-stone-600 leading-relaxed">{{ $order->notes }}</p>
                </div>
            @endif
        </div>
    </div>

    <div class="mt-10 flex flex-col sm:flex-row gap-3 justify-center">
        <a href="{{ route('shop.catalog') }}" class="inline-flex justify-center items-center rounded-full bg-brand hover:bg-brand-dark text-white px-6 py-3 text-sm font-semibold transition-colors">Continue Shopping</a>
        <a href="{{ route('account.dashboard') }}" class="inline-flex justify-center items-center rounded-full border border-[var(--line)] hover:border-brand hover:text-brand px-6 py-3 text-sm font-semibold transition-colors">View My Orders</a>
    </div>
</div>
@endsection
