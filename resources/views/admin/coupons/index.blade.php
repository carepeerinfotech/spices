@extends('admin.layouts.app')

@section('title', 'Coupons')
@section('heading', 'Coupons')

@section('content')
<div class="flex justify-end mb-5">
    <a href="{{ route('admin.coupons.create') }}" class="rounded-lg bg-teal-700 hover:bg-teal-600 text-white px-4 py-2 text-sm">Add coupon</a>
</div>
<div class="rounded-xl bg-white border border-slate-200 overflow-hidden">
    <table class="min-w-full text-sm">
        <thead class="bg-slate-50 text-slate-500 text-left">
        <tr>
            <th class="px-5 py-3 font-medium">Code</th>
            <th class="px-5 py-3 font-medium">Type</th>
            <th class="px-5 py-3 font-medium">Value</th>
            <th class="px-5 py-3 font-medium">Validity</th>
            <th class="px-5 py-3 font-medium">Status</th>
            <th class="px-5 py-3 font-medium"></th>
        </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
        @forelse($coupons as $coupon)
            <tr>
                <td class="px-5 py-3 font-medium">{{ $coupon->code }}</td>
                <td class="px-5 py-3 capitalize">{{ $coupon->discount_type }}</td>
                <td class="px-5 py-3">{{ $coupon->label() }}</td>
                <td class="px-5 py-3 text-slate-500">
                    @if($coupon->starts_at || $coupon->ends_at)
                        {{ $coupon->starts_at?->format('d M Y') ?? 'Any' }} – {{ $coupon->ends_at?->format('d M Y') ?? 'No end' }}
                    @else
                        Always
                    @endif
                </td>
                <td class="px-5 py-3">
                    <span class="{{ $coupon->is_active ? 'text-emerald-700' : 'text-slate-400' }}">{{ $coupon->is_active ? 'Active' : 'Inactive' }}</span>
                </td>
                <td class="px-5 py-3 text-right">
                    <a href="{{ route('admin.coupons.edit', $coupon) }}" class="text-teal-700 hover:underline mr-3">Edit</a>
                    <button type="button" data-delete="{{ route('admin.coupons.destroy', $coupon) }}" class="text-rose-600 hover:underline">Delete</button>
                </td>
            </tr>
        @empty
            <tr>
                <td class="px-5 py-6 text-slate-500" colspan="6">No coupons yet.</td>
            </tr>
        @endforelse
        </tbody>
    </table>
</div>
<div class="mt-4">{{ $coupons->links() }}</div>
@endsection
