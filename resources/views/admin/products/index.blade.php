@extends('admin.layouts.app')

@section('title', 'Products')
@section('heading', 'Products')

@section('content')
<div class="flex flex-wrap items-center justify-between gap-3 mb-5">
    <form method="GET" class="flex gap-2">
        <input type="search" name="q" value="{{ request('q') }}" placeholder="Search products..."
               class="rounded-lg border border-slate-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-teal-500">
        <button class="rounded-lg bg-slate-800 text-white px-3 py-2 text-sm">Search</button>
    </form>
    <a href="{{ route('admin.products.create') }}" class="rounded-lg bg-teal-700 hover:bg-teal-600 text-white px-4 py-2 text-sm">Add product</a>
</div>
<div class="rounded-xl bg-white border border-slate-200 overflow-hidden">
    <table class="min-w-full text-sm">
        <thead class="bg-slate-50 text-slate-500 text-left">
        <tr>
            <th class="px-5 py-3 font-medium">Product</th>
            <th class="px-5 py-3 font-medium">SKU</th>
            <th class="px-5 py-3 font-medium">Price</th>
            <th class="px-5 py-3 font-medium">Stock</th>
            <th class="px-5 py-3 font-medium">Status</th>
            <th class="px-5 py-3 font-medium"></th>
        </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
        @foreach($products as $product)
            <tr>
                <td class="px-5 py-3">
                    <div class="font-medium">{{ $product->name }}</div>
                    <div class="text-xs text-slate-500">{{ $product->category?->name }}</div>
                </td>
                <td class="px-5 py-3 font-mono text-xs">{{ $product->sku }}</td>
                <td class="px-5 py-3">${{ number_format($product->price, 2) }}</td>
                <td class="px-5 py-3">{{ $product->stock }}</td>
                <td class="px-5 py-3">{{ $product->is_active ? 'Active' : 'Hidden' }}</td>
                <td class="px-5 py-3 text-right whitespace-nowrap">
                    <a href="{{ route('admin.products.edit', $product) }}" class="text-teal-700 hover:underline mr-3">Edit</a>
                    <button type="button" data-delete="{{ route('admin.products.destroy', $product) }}" class="text-rose-600 hover:underline">Delete</button>
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>
<div class="mt-4">{{ $products->withQueryString()->links() }}</div>
@endsection
