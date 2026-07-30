@extends('shop.layouts.app')

@section('title', 'Paytm Sandbox')

@section('content')
<div class="max-w-md mx-auto px-4 py-16">
    <div class="rounded-xl border border-slate-200 bg-white p-6 text-center">
        <h1 class="font-display text-2xl mb-2">Fake Paytm Checkout</h1>
        <p class="text-sm text-slate-500 mb-4">Order {{ $transaction->order->order_number }}</p>
        <p class="text-3xl font-semibold text-brand mb-6">₹{{ number_format($transaction->amount, 2) }}</p>
        <form method="POST" action="{{ route('payments.paytm.fake.complete', $transaction) }}" class="space-y-3">
            @csrf
            <button name="status" value="success" class="w-full rounded-lg bg-emerald-600 text-white py-2.5 text-sm">Simulate success</button>
            <button name="status" value="failed" class="w-full rounded-lg bg-rose-600 text-white py-2.5 text-sm">Simulate failure</button>
        </form>
    </div>
</div>
@endsection
