@extends('admin.layouts.app')

@section('title', 'CMS Pages')
@section('heading', 'CMS Pages')
@section('subtitle', 'Static content for the storefront')

@section('content')
<div class="flex flex-wrap items-center justify-between gap-3 mb-5">
    <form method="GET" class="flex gap-2">
        <input type="search" name="q" value="{{ request('q') }}" placeholder="Search pages..."
               class="rounded-lg border border-slate-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-teal-500">
        <button class="rounded-lg bg-slate-800 text-white px-3 py-2 text-sm">Search</button>
    </form>
    <a href="{{ route('admin.pages.create') }}" class="rounded-lg bg-teal-700 hover:bg-teal-600 text-white px-4 py-2 text-sm">Add page</a>
</div>

<div class="rounded-xl bg-white border border-slate-200 overflow-hidden">
    <table class="min-w-full text-sm">
        <thead class="bg-slate-50 text-slate-500 text-left">
        <tr>
            <th class="px-5 py-3 font-medium">Title</th>
            <th class="px-5 py-3 font-medium">Slug</th>
            <th class="px-5 py-3 font-medium">Status</th>
            <th class="px-5 py-3 font-medium">Updated</th>
            <th class="px-5 py-3 font-medium"></th>
        </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
        @foreach($pages as $page)
            <tr>
                <td class="px-5 py-3 font-medium">{{ $page->title }}</td>
                <td class="px-5 py-3 font-mono text-xs">
                    <a href="{{ route('shop.page', $page->slug) }}" target="_blank" class="text-teal-700 hover:underline">{{ $page->slug }}</a>
                </td>
                <td class="px-5 py-3 capitalize">{{ $page->status }}</td>
                <td class="px-5 py-3 text-slate-500">{{ $page->updated_at->format('M j, Y') }}</td>
                <td class="px-5 py-3 text-right whitespace-nowrap">
                    <a href="{{ route('admin.pages.edit', $page) }}" class="text-teal-700 hover:underline mr-3">Edit</a>
                    <button type="button" data-delete="{{ route('admin.pages.destroy', $page) }}" class="text-rose-600 hover:underline">Delete</button>
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>
<div class="mt-4">{{ $pages->withQueryString()->links() }}</div>
@endsection
