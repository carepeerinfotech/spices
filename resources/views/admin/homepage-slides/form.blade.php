@extends('admin.layouts.app')

@section('title', $slide->exists ? 'Edit Slide' : 'Add Slide')
@section('heading', $slide->exists ? 'Edit slide' : 'Add slide')
@section('subtitle', 'Desktop and mobile banner images for the homepage hero slider')

@section('content')
<form data-ajax="formdata" method="POST" enctype="multipart/form-data"
      action="{{ $slide->exists ? route('admin.homepage-slides.update', $slide) : route('admin.homepage-slides.store') }}"
      class="max-w-2xl rounded-xl bg-white border border-slate-200 p-6 space-y-5">
    @csrf
    @if($slide->exists) @method('PUT') @endif

    <div>
        <label class="block text-sm font-medium mb-1.5">Title <span class="text-slate-400 font-normal">(admin label)</span></label>
        <input name="title" value="{{ old('title', $slide->title) }}" class="w-full rounded-lg border border-slate-300 px-3 py-2" placeholder="e.g. Summer collection">
    </div>

    <div>
        <label class="block text-sm font-medium mb-1.5">Link URL <span class="text-slate-400 font-normal">(optional)</span></label>
        <input name="link_url" value="{{ old('link_url', $slide->link_url) }}" class="w-full rounded-lg border border-slate-300 px-3 py-2" placeholder="/shop or https://…">
    </div>

    <div class="grid sm:grid-cols-2 gap-4">
        <div>
            <label class="block text-sm font-medium mb-1.5">Desktop image {{ $slide->exists ? '' : '*' }}</label>
            <input type="file" name="image_file" accept="image/*" class="block w-full text-sm" @unless($slide->exists) required @endunless>
            <p class="text-xs text-slate-500 mt-1">Recommended ~1440×768</p>
            @if($slide->imageUrl())
                <img src="{{ $slide->imageUrl() }}" alt="" class="mt-2 h-28 w-full rounded object-cover border">
            @endif
            <input name="image_url" value="{{ old('image_url', str_starts_with((string) $slide->image, 'http') ? $slide->image : '') }}" class="mt-2 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" placeholder="Or paste image URL">
        </div>
        <div>
            <label class="block text-sm font-medium mb-1.5">Mobile image</label>
            <input type="file" name="mobile_image_file" accept="image/*" class="block w-full text-sm">
            <p class="text-xs text-slate-500 mt-1">Recommended ~600×780 — falls back to desktop</p>
            @if($slide->mobileImageUrl() && $slide->mobile_image)
                <img src="{{ $slide->mobileImageUrl() }}" alt="" class="mt-2 h-28 w-full rounded object-cover border">
            @endif
            <input name="mobile_image_url" value="{{ old('mobile_image_url', str_starts_with((string) $slide->mobile_image, 'http') ? $slide->mobile_image : '') }}" class="mt-2 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" placeholder="Or paste mobile image URL">
        </div>
    </div>

    <div class="grid sm:grid-cols-2 gap-4">
        <div>
            <label class="block text-sm font-medium mb-1.5">Sort order</label>
            <input type="number" name="sort_order" min="0" value="{{ old('sort_order', $slide->sort_order ?? 0) }}" class="w-full rounded-lg border border-slate-300 px-3 py-2">
        </div>
        <div class="flex items-end pb-2">
            <label class="inline-flex items-center gap-2 text-sm">
                <input type="checkbox" name="is_active" value="1" data-bool @checked(old('is_active', $slide->is_active ?? true))>
                Active
            </label>
        </div>
    </div>

    <div class="flex gap-3">
        <button type="submit" class="rounded-lg bg-teal-700 hover:bg-teal-600 text-white px-4 py-2 text-sm">Save slide</button>
        <a href="{{ route('admin.homepage-slides.index') }}" class="rounded-lg border border-slate-300 px-4 py-2 text-sm text-slate-700">Cancel</a>
    </div>
</form>
@endsection
