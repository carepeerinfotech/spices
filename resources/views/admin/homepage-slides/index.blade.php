@extends('admin.layouts.app')

@section('title', 'Homepage Slider')
@section('heading', 'Homepage Slider')
@section('subtitle', 'Full-bleed hero banners like a brand homepage — managed here')

@section('content')
<div class="flex justify-between items-center mb-5 gap-3 flex-wrap">
    <p class="text-sm text-slate-500">Active slides auto-rotate on the storefront. Use desktop + mobile images for best results.</p>
    <a href="{{ route('admin.homepage-slides.create') }}" class="rounded-lg bg-teal-700 hover:bg-teal-600 text-white px-4 py-2 text-sm">Add slide</a>
</div>

<div class="rounded-xl bg-white border border-slate-200 overflow-hidden">
    <table class="min-w-full text-sm">
        <thead class="bg-slate-50 text-slate-500 text-left">
        <tr>
            <th class="px-5 py-3 font-medium">Preview</th>
            <th class="px-5 py-3 font-medium">Title</th>
            <th class="px-5 py-3 font-medium">Sort</th>
            <th class="px-5 py-3 font-medium">Status</th>
            <th class="px-5 py-3 font-medium"></th>
        </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
        @forelse($slides as $slide)
            <tr class="js-deletable">
                <td class="px-5 py-3">
                    @if($slide->imageUrl())
                        <img src="{{ $slide->imageUrl() }}" alt="" class="h-14 w-28 rounded object-cover border border-slate-100">
                    @endif
                </td>
                <td class="px-5 py-3 font-medium">{{ $slide->title ?: '—' }}</td>
                <td class="px-5 py-3">{{ $slide->sort_order }}</td>
                <td class="px-5 py-3">{{ $slide->is_active ? 'Active' : 'Inactive' }}</td>
                <td class="px-5 py-3 text-right whitespace-nowrap">
                    <a href="{{ route('admin.homepage-slides.edit', $slide) }}" class="text-teal-700 hover:underline mr-3">Edit</a>
                    <button type="button" data-delete="{{ route('admin.homepage-slides.destroy', $slide) }}" data-confirm="Delete this slide?" class="text-rose-600 hover:underline">Delete</button>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="5" class="px-5 py-10 text-center text-slate-500">No slides yet. Add your first banner.</td>
            </tr>
        @endforelse
        </tbody>
    </table>
</div>
@endsection
