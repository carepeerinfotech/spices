@extends('admin.layouts.app')
@section('title', 'Homepage')
@section('heading', 'Homepage CMS')
@section('subtitle', 'Manage hero, categories, featured products and trust blocks')

@section('content')
@php
    $hero = $sections->get('hero');
    $cats = $sections->get('categories');
    $featured = $sections->get('featured');
    $trust = $sections->get('trust');
@endphp
<form data-ajax method="POST" action="{{ route('admin.homepage.update') }}" class="space-y-6">
    @csrf @method('PUT')

    <div class="rounded-xl bg-white border p-5 space-y-3">
        <div class="flex justify-between"><h2 class="font-medium">Hero</h2>
            <label class="text-sm"><input type="checkbox" name="sections[hero][is_enabled]" value="1" @checked($hero->is_enabled ?? true)> Enabled</label>
        </div>
        <input type="hidden" name="sections[hero][type]" value="hero">
        <input type="hidden" name="sections[hero][is_published]" value="1">
        <input type="hidden" name="sections[hero][sort_order]" value="1">
        <input name="sections[hero][content][brand]" value="{{ data_get($hero?->content, 'brand', 'Elephant') }}" class="w-full rounded-lg border px-3 py-2" placeholder="Brand">
        <input name="sections[hero][content][headline]" value="{{ data_get($hero?->content, 'headline') }}" class="w-full rounded-lg border px-3 py-2" placeholder="Headline">
        <input name="sections[hero][content][subline]" value="{{ data_get($hero?->content, 'subline') }}" class="w-full rounded-lg border px-3 py-2" placeholder="Subline">
        <input name="sections[hero][content][cta_label]" value="{{ data_get($hero?->content, 'cta_label', 'Shop now') }}" class="w-full rounded-lg border px-3 py-2" placeholder="CTA label">
        <input name="sections[hero][content][cta_url]" value="{{ data_get($hero?->content, 'cta_url', '/shop') }}" class="w-full rounded-lg border px-3 py-2" placeholder="CTA URL">
        <input name="sections[hero][content][image]" value="{{ data_get($hero?->content, 'image') }}" class="w-full rounded-lg border px-3 py-2" placeholder="Image URL">
    </div>

    <div class="rounded-xl bg-white border p-5 space-y-3">
        <div class="flex justify-between"><h2 class="font-medium">Categories</h2>
            <label class="text-sm"><input type="checkbox" name="sections[categories][is_enabled]" value="1" @checked($cats->is_enabled ?? true)> Enabled</label>
        </div>
        <input type="hidden" name="sections[categories][type]" value="categories">
        <input type="hidden" name="sections[categories][is_published]" value="1">
        <input type="hidden" name="sections[categories][sort_order]" value="2">
        <input name="sections[categories][title]" value="{{ $cats->title ?? 'Shop by category' }}" class="w-full rounded-lg border px-3 py-2">
        <div class="grid sm:grid-cols-2 gap-2">
            @foreach($categories as $category)
                <label class="text-sm inline-flex gap-2 items-center">
                    <input type="checkbox" name="sections[categories][content][category_ids][]" value="{{ $category->id }}"
                           @checked(in_array($category->id, data_get($cats?->content, 'category_ids', [])))>
                    {{ $category->name }}
                </label>
            @endforeach
        </div>
    </div>

    <div class="rounded-xl bg-white border p-5 space-y-3">
        <div class="flex justify-between"><h2 class="font-medium">Featured products</h2>
            <label class="text-sm"><input type="checkbox" name="sections[featured][is_enabled]" value="1" @checked($featured->is_enabled ?? true)> Enabled</label>
        </div>
        <input type="hidden" name="sections[featured][type]" value="featured">
        <input type="hidden" name="sections[featured][is_published]" value="1">
        <input type="hidden" name="sections[featured][sort_order]" value="3">
        <input name="sections[featured][title]" value="{{ $featured->title ?? 'Featured' }}" class="w-full rounded-lg border px-3 py-2">
        <div class="grid sm:grid-cols-2 gap-2 max-h-64 overflow-auto">
            @foreach($products as $product)
                <label class="text-sm inline-flex gap-2 items-center">
                    <input type="checkbox" name="sections[featured][content][product_ids][]" value="{{ $product->id }}"
                           @checked(in_array($product->id, data_get($featured?->content, 'product_ids', [])))>
                    {{ $product->name }}
                </label>
            @endforeach
        </div>
    </div>

    <div class="rounded-xl bg-white border p-5 space-y-3">
        <div class="flex justify-between"><h2 class="font-medium">Trust blocks</h2>
            <label class="text-sm"><input type="checkbox" name="sections[trust][is_enabled]" value="1" @checked($trust->is_enabled ?? true)> Enabled</label>
        </div>
        <input type="hidden" name="sections[trust][type]" value="trust">
        <input type="hidden" name="sections[trust][is_published]" value="1">
        <input type="hidden" name="sections[trust][sort_order]" value="4">
        @for($i = 0; $i < 3; $i++)
            <div class="grid sm:grid-cols-2 gap-2">
                <input name="sections[trust][content][items][{{ $i }}][title]" value="{{ data_get($trust?->content, 'items.'.$i.'.title') }}" class="rounded-lg border px-3 py-2" placeholder="Title {{ $i+1 }}">
                <input name="sections[trust][content][items][{{ $i }}][text]" value="{{ data_get($trust?->content, 'items.'.$i.'.text') }}" class="rounded-lg border px-3 py-2" placeholder="Text {{ $i+1 }}">
            </div>
        @endfor
    </div>

    <button class="rounded-lg bg-teal-700 text-white px-4 py-2 text-sm">Save homepage</button>
</form>
@endsection
