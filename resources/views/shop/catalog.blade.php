@extends('shop.layouts.app')

@section('title', ($activeCategory->name ?? 'Shop').' — Elephant Spices')

@section('content')
<div class="page-hero-band">
    <div class="max-w-6xl mx-auto px-4 sm:px-6">
        @if(!empty($activeCategory?->bannerUrl()))
            <div class="rounded-2xl overflow-hidden mb-6 h-40 sm:h-56 shadow-md shadow-stone-900/5">
                <img src="{{ $activeCategory->bannerUrl() }}" alt="{{ $activeCategory->name }}" class="w-full h-full object-cover">
            </div>
        @endif
        <div class="flex flex-col sm:flex-row sm:items-end justify-between gap-4">
            <div>
                <h1>{{ $activeCategory->name ?? 'Our Spices' }}</h1>
                <p class="text-stone-500 mt-1 text-sm">{{ $activeCategory->description ?? 'Browse pure and blended Elephant Spices' }}</p>
            </div>
            <form method="GET" class="flex gap-2 w-full sm:w-auto">
                <input type="search" name="q" value="{{ request('q') }}" placeholder="Search spices..."
                       class="flex-1 sm:flex-none rounded-full border border-[var(--line)] bg-white px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-brand/30">
                @if(request('category'))
                    <input type="hidden" name="category" value="{{ request('category') }}">
                @endif
                <button class="btn-brand !rounded-full !px-4 !py-2 text-sm">Search</button>
            </form>
        </div>
    </div>
</div>

<div class="max-w-6xl mx-auto px-4 sm:px-6 py-8 sm:py-10">
    <div class="flex flex-wrap gap-2 mb-8">
        <a href="{{ route('shop.catalog') }}" class="rounded-full px-3.5 py-1.5 text-sm border {{ !request('category') ? 'bg-brand text-white border-brand' : 'border-[var(--line)] bg-white text-stone-600' }}">All</a>
        @foreach($categories as $category)
            <a href="{{ route('shop.catalog', ['category' => $category->slug]) }}"
               class="rounded-full px-3.5 py-1.5 text-sm border {{ request('category') === $category->slug ? 'bg-brand text-white border-brand' : 'border-[var(--line)] bg-white text-stone-600' }}">
                {{ $category->name }}
            </a>
        @endforeach
    </div>

    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-5">
        @forelse($products as $product)
            @include('shop.partials.product-card', ['product' => $product])
        @empty
            <p class="col-span-full text-center text-stone-500 py-16">No products found.</p>
        @endforelse
    </div>

    <div class="mt-8">{{ $products->withQueryString()->links() }}</div>
</div>
@endsection
