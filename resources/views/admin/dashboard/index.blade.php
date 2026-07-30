@extends('admin.layouts.app')

@section('title', 'Dashboard')
@section('heading', 'Dashboard')
@section('subtitle', 'Store overview')

@section('content')
@unless($storageReady)
<div class="mb-6 rounded-xl border border-amber-200 bg-amber-50 px-5 py-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
    <div>
        <p class="font-medium text-amber-900">Storage link missing</p>
        <p class="text-sm text-amber-800 mt-1">Uploaded images won’t show until public storage is linked. No SSH needed — click the button.</p>
    </div>
    <form data-ajax method="POST" action="{{ route('admin.storage-link') }}">
        @csrf
        <button type="submit" class="rounded-lg bg-amber-700 hover:bg-amber-600 text-white px-4 py-2 text-sm whitespace-nowrap">Fix storage link</button>
    </form>
</div>
@endunless

<div class="grid sm:grid-cols-2 xl:grid-cols-3 gap-4 mb-8">
    @foreach([
        ['Products', $stats['products'], 'teal'],
        ['Orders', $stats['orders'], 'sky'],
        ['Pending', $stats['pending_orders'], 'amber'],
        ['Users', $stats['users'], 'violet'],
        ['CMS Pages', $stats['pages'], 'emerald'],
        ['Revenue', '$'.number_format($stats['revenue'], 2), 'rose'],
    ] as [$label, $value, $color])
        <div class="rounded-xl bg-white border border-slate-200 p-5">
            <p class="text-sm text-slate-500">{{ $label }}</p>
            <p class="mt-2 font-display text-3xl text-{{ $color }}-700">{{ $value }}</p>
        </div>
    @endforeach
</div>

<div class="rounded-xl bg-white border border-slate-200 overflow-hidden">
    <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
        <h2 class="font-medium">Recent orders</h2>
        <a href="{{ route('admin.orders.index') }}" class="text-sm text-teal-700 hover:underline">View all</a>
    </div>
    <div class="overflow-x-auto">
        <table class="min-w-full text-sm">
            <thead class="bg-slate-50 text-slate-500 text-left">
            <tr>
                <th class="px-5 py-3 font-medium">Order</th>
                <th class="px-5 py-3 font-medium">Customer</th>
                <th class="px-5 py-3 font-medium">Total</th>
                <th class="px-5 py-3 font-medium">Status</th>
            </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
            @forelse($recentOrders as $order)
                <tr>
                    <td class="px-5 py-3">
                        <a href="{{ route('admin.orders.show', $order) }}" class="text-teal-700 hover:underline">{{ $order->order_number }}</a>
                    </td>
                    <td class="px-5 py-3">{{ $order->customer_name }}</td>
                    <td class="px-5 py-3">${{ number_format($order->total, 2) }}</td>
                    <td class="px-5 py-3 capitalize">{{ $order->status }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="4" class="px-5 py-8 text-center text-slate-500">No orders yet.</td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
