@extends('admin.layouts.app')

@section('title', 'Categories')
@section('heading', 'Categories')

@section('content')
<div class="flex justify-end mb-5">
    <a href="{{ route('admin.categories.create') }}" class="rounded-lg bg-teal-700 hover:bg-teal-600 text-white px-4 py-2 text-sm">Add category</a>
</div>
<div class="rounded-xl bg-white border border-slate-200 overflow-hidden">
    <table class="min-w-full text-sm">
        <thead class="bg-slate-50 text-slate-500 text-left">
        <tr>
            <th class="px-5 py-3 font-medium">Name</th>
            <th class="px-5 py-3 font-medium">Products</th>
            <th class="px-5 py-3 font-medium">Sort</th>
            <th class="px-5 py-3 font-medium">Status</th>
            <th class="px-5 py-3 font-medium"></th>
        </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
        @foreach($categories as $category)
            <tr>
                <td class="px-5 py-3 font-medium">{{ $category->name }}</td>
                <td class="px-5 py-3">{{ $category->products_count }}</td>
                <td class="px-5 py-3">{{ $category->sort_order }}</td>
                <td class="px-5 py-3">{{ $category->is_active ? 'Active' : 'Inactive' }}</td>
                <td class="px-5 py-3 text-right">
                    <a href="{{ route('admin.categories.edit', $category) }}" class="text-teal-700 hover:underline mr-3">Edit</a>
                    <button type="button" data-delete="{{ route('admin.categories.destroy', $category) }}" class="text-rose-600 hover:underline">Delete</button>
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>
<div class="mt-4">{{ $categories->links() }}</div>
@endsection
