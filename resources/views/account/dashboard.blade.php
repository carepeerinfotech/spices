@extends('shop.layouts.app')
@section('title', 'My Account')
@section('content')
<div class="max-w-4xl mx-auto px-4 sm:px-6 py-10 space-y-8">
    <div class="flex items-center justify-between">
        <h1 class="font-display text-3xl">My account</h1>
        <form method="POST" action="{{ route('logout') }}">@csrf<button class="text-sm text-rose-600">Logout</button></form>
    </div>

    @if(\App\Support\Features::emailVerification() && ! auth()->user()->hasVerifiedEmail())
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

        @if(\App\Support\Features::passwordReset())
            <form data-ajax method="POST" action="{{ route('account.password.link') }}" class="rounded-xl border bg-white p-5 space-y-3">
                @csrf
                <h2 class="font-medium">Password</h2>
                <p class="text-sm text-slate-500">
                    We'll email a secure link to <span class="font-medium text-slate-700">{{ $user->email }}</span>
                    so you can set a new password.
                </p>
                <button type="submit" data-loading="Sending..." class="rounded-lg bg-brand text-white px-3 py-2 text-sm">Email me a reset link</button>
            </form>
        @else
            {{-- No reset link would resolve with the feature off, so fall back to
                 changing the password in place. --}}
            <form data-ajax method="POST" action="{{ route('account.password') }}" class="rounded-xl border bg-white p-5 space-y-3">
                @csrf @method('PUT')
                <h2 class="font-medium">Change password</h2>
                <input type="password" name="current_password" placeholder="Current password" required class="w-full rounded-lg border px-3 py-2 text-sm">
                <input type="password" name="password" placeholder="New password" required class="w-full rounded-lg border px-3 py-2 text-sm">
                <input type="password" name="password_confirmation" placeholder="Confirm password" required class="w-full rounded-lg border px-3 py-2 text-sm">
                <button class="rounded-lg bg-brand text-white px-3 py-2 text-sm">Update password</button>
            </form>
        @endif
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
                <div class="flex items-center justify-between gap-3 border-b pb-2">
                    <div class="min-w-0">
                        <p class="font-medium truncate">{{ $order->order_number }}</p>
                        <p class="text-xs text-slate-500">{{ $order->created_at->format('M j, Y') }} · {{ ucfirst($order->status) }}</p>
                    </div>
                    <div class="flex items-center gap-4 shrink-0">
                        <span>₹{{ number_format($order->total, 2) }}</span>
                        <a href="{{ route('shop.checkout.success', $order->order_number) }}" class="text-brand whitespace-nowrap">View details</a>
                    </div>
                </div>
            @empty
                <p class="text-slate-500">No orders yet.</p>
            @endforelse
        </div>
    </div>
</div>
@endsection
