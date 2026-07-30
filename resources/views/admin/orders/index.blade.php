@extends('admin.layouts.app')

@section('title', 'Orders')
@section('heading', 'Orders')

@section('content')
<form method="GET" class="flex flex-wrap gap-2 mb-5">
    <input type="search" name="q" value="{{ request('q') }}" placeholder="Search orders..."
           class="rounded-lg border border-slate-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-teal-500">
    <select name="status" class="rounded-lg border border-slate-300 px-3 py-2 text-sm">
        <option value="">All statuses</option>
        @foreach(['pending','processing','shipped','delivered','cancelled'] as $status)
            <option value="{{ $status }}" @selected(request('status') === $status)>{{ ucfirst($status) }}</option>
        @endforeach
    </select>
    <button class="rounded-lg bg-slate-800 text-white px-3 py-2 text-sm">Filter</button>
</form>

<div class="rounded-xl bg-white border border-slate-200 overflow-hidden">
    <table class="min-w-full text-sm">
        <thead class="bg-slate-50 text-slate-500 text-left">
        <tr>
            <th class="px-5 py-3 font-medium">Order</th>
            <th class="px-5 py-3 font-medium">Customer</th>
            <th class="px-5 py-3 font-medium">Items</th>
            <th class="px-5 py-3 font-medium">Total</th>
            <th class="px-5 py-3 font-medium">Status</th>
            <th class="px-5 py-3 font-medium">Date</th>
        </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
        @forelse($orders as $order)
            <tr>
                <td class="px-5 py-3">
                    <a href="{{ route('admin.orders.show', $order) }}" class="text-teal-700 hover:underline font-medium">{{ $order->order_number }}</a>
                </td>
                <td class="px-5 py-3">
                    <div>{{ $order->customer_name }}</div>
                    <div class="text-xs text-slate-500">{{ $order->customer_email }}</div>
                </td>
                <td class="px-5 py-3">{{ $order->items_count }}</td>
                <td class="px-5 py-3">${{ number_format($order->total, 2) }}</td>
                <td class="px-5 py-3 capitalize">{{ $order->status }}</td>
                <td class="px-5 py-3 text-slate-500">{{ $order->created_at->format('M j, Y') }}</td>
            </tr>
        @empty
            <tr><td colspan="6" class="px-5 py-8 text-center text-slate-500">No orders found.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
<div class="mt-4">{{ $orders->withQueryString()->links() }}</div>
@endsection
