@extends('shop.layouts.app')
@section('title', 'My Account')
@section('content')
<div class="max-w-4xl mx-auto px-4 sm:px-6 py-10 space-y-8">
    <div class="flex items-center justify-between">
        <h1 class="font-display text-3xl">My account</h1>
        <form method="POST" action="{{ route('logout') }}">@csrf<button class="text-sm text-rose-600">Logout</button></form>
    </div>

    @if(! auth()->user()->hasVerifiedEmail())
        <div class="rounded-lg bg-amber-50 border border-amber-200 p-4 text-sm">
            Email not verified.
            <a href="{{ route('verification.notice') }}" class="text-brand underline">Verify now</a>
        </div>
    @endif

    <div class="grid md:grid-cols-2 gap-6">
        <form data-ajax method="POST" action="{{ route('account.profile') }}" class="rounded-xl border bg-white p-5 space-y-3">
            @csrf @method('PUT')
            <h2 class="font-medium">Profile</h2>
            <input name="name" value="{{ $user->name }}" class="w-full rounded-lg border px-3 py-2 text-sm" required>
            <input name="phone" value="{{ $user->phone }}" class="w-full rounded-lg border px-3 py-2 text-sm" placeholder="Phone">
            <p class="text-xs text-slate-500">{{ $user->email }}</p>
            <button class="rounded-lg bg-brand text-white px-3 py-2 text-sm">Save profile</button>
        </form>

        <form data-ajax method="POST" action="{{ route('account.password') }}" class="rounded-xl border bg-white p-5 space-y-3">
            @csrf @method('PUT')
            <h2 class="font-medium">Change password</h2>
            <input type="password" name="current_password" placeholder="Current password" required class="w-full rounded-lg border px-3 py-2 text-sm">
            <input type="password" name="password" placeholder="New password" required class="w-full rounded-lg border px-3 py-2 text-sm">
            <input type="password" name="password_confirmation" placeholder="Confirm password" required class="w-full rounded-lg border px-3 py-2 text-sm">
            <button class="rounded-lg bg-brand text-white px-3 py-2 text-sm">Update password</button>
        </form>
    </div>

    <div class="rounded-xl border bg-white p-5">
        <div class="flex justify-between mb-3">
            <h2 class="font-medium">Shipping addresses</h2>
            <a href="{{ route('account.addresses.index') }}" class="text-sm text-brand">Manage</a>
        </div>
        <ul class="text-sm space-y-2">
            @forelse($user->addresses as $address)
                <li>{{ $address->label }}: {{ $address->fullAddress() }}</li>
            @empty
                <li class="text-slate-500">No addresses yet.</li>
            @endforelse
        </ul>
    </div>

    <div class="rounded-xl border bg-white p-5">
        <h2 class="font-medium mb-3">Recent orders</h2>
        <div class="space-y-2 text-sm">
            @forelse($user->orders as $order)
                <div class="flex justify-between border-b pb-2">
                    <span>{{ $order->order_number }}</span>
                    <span>₹{{ number_format($order->total, 2) }} · {{ $order->status }}</span>
                </div>
            @empty
                <p class="text-slate-500">No orders yet.</p>
            @endforelse
        </div>
    </div>
</div>
@endsection
