@extends('shop.layouts.app')

@section('title', 'Order confirmed — '.config('app.name'))

@section('content')
<x-shop.breadcrumb title="Order Confirmed" />
<div class="max-w-xl mx-auto px-4 sm:px-6 py-16 text-center">
    <p class="text-emerald-700 text-sm font-medium mb-4">Thank you for your purchase</p>
    <p class="text-slate-600 mb-2">Your order number is</p>
    <p class="font-mono text-lg font-semibold text-brand mb-6">{{ $order->order_number }}</p>
    <p class="text-sm text-slate-500 mb-8">We've sent a confirmation to {{ $order->customer_email }}. Total charged: ${{ number_format($order->total, 2) }}.</p>
    <a href="{{ route('shop.catalog') }}" class="inline-flex rounded-lg bg-brand hover:bg-brand-dark text-white px-5 py-2.5 text-sm font-medium">Continue shopping</a>
</div>
@endsection
