@extends('shop.layouts.app')

@section('title', ($activeCategory->name ?? 'Shop') . ' — Elephant Spices')

@section('content')
    <x-shop.breadcrumb :title="$activeCategory->name ?? 'Our Spices'" :slider="true" />

    <div class="max-w-6xl mx-auto px-4 sm:px-6 py-8 sm:py-10">
        <div class="lg:grid lg:grid-cols-10 lg:gap-8 lg:items-start">
            <aside class="lg:col-span-3 mb-8 lg:mb-0 lg:sticky lg:top-24">
                <div class="space-y-5">
                    <form method="GET" class="relative">
                        @if(request('category'))
                            <input type="hidden" name="category" value="{{ request('category') }}">
                        @endif
                        <input type="search" name="q" value="{{ request('q') }}" placeholder="Search spices..."
                            class="w-full rounded-full border border-[var(--line)] bg-white pl-4 pr-11 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-brand/30">
                        <button type="submit" aria-label="Search"
                            class="absolute right-1 top-1/2 -translate-y-1/2 w-8 h-8 rounded-full bg-brand text-white grid place-items-center">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="2">
                                <circle cx="11" cy="11" r="7" />
                                <path d="m20 20-3.5-3.5" />
                            </svg>
                        </button>
                    </form>

                    <div class="rounded-2xl bg-white border border-[var(--line)] overflow-hidden">
                        <button type="button"
                            class="cat-toggle w-full flex items-center justify-between px-4 py-3 text-sm font-semibold text-stone-800 border-b border-[var(--line)] lg:pointer-events-none"
                            aria-expanded="false" aria-controls="category-list">
                            Categories
                            <svg class="cat-chevron w-4 h-4 text-stone-400 transition-transform lg:hidden"
                                viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="m6 9 6 6 6-6" />
                            </svg>
                        </button>
                        <nav id="category-list"
                            class="hidden lg:block shop-category-scroll lg:overflow-auto lg:h-[67vh] divide-y divide-[var(--line)]">
                            <a href="{{ route('shop.catalog', array_filter(['q' => request('q')])) }}"
                                class="flex items-center px-4 py-3 text-sm transition-colors {{ !request('category') ? 'text-brand font-semibold bg-cream' : 'text-stone-600 hover:bg-cream/60' }}">
                                All Spices
                            </a>
                            @foreach($categories as $category)
                                <a href="{{ route('shop.catalog', array_filter(['category' => $category->slug, 'q' => request('q')])) }}"
                                    class="flex items-center gap-3 px-4 py-3 text-sm transition-colors {{ request('category') === $category->slug ? 'text-brand font-semibold bg-cream' : 'text-stone-600 hover:bg-cream/60' }}">
                                    @if($category->imageUrl())
                                        <img src="{{ $category->imageUrl() }}" alt=""
                                            class="w-7 h-7 rounded-full object-cover shrink-0">
                                    @endif
                                    <span>{{ $category->name }}</span>
                                </a>
                            @endforeach
                        </nav>
                    </div>
                </div>
            </aside>

            <div class="lg:col-span-7">
                @if($activeCategory?->description)
                    <p class="text-stone-500 text-sm mb-6">{{ $activeCategory->description }}</p>
                @endif
                @if(!empty($activeCategory?->bannerUrl()))
                    <div class="rounded-2xl overflow-hidden mb-6 h-32 sm:h-44 shadow-md shadow-stone-900/5">
                        <img src="{{ $activeCategory->bannerUrl() }}" alt="{{ $activeCategory->name }}"
                            class="w-full h-full object-cover">
                    </div>
                @endif

                <div class="grid grid-cols-2 md:grid-cols-3 gap-3 sm:gap-5">
                    @forelse($products as $product)
                        @include('shop.partials.product-card', ['product' => $product])
                    @empty
                        <p class="col-span-full text-center text-stone-500 py-16">No products found.</p>
                    @endforelse
                </div>

                <div class="mt-8">{{ $products->withQueryString()->links() }}</div>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            (function () {
                var toggle = document.querySelector('.cat-toggle');
                var list = document.getElementById('category-list');
                if (!toggle || !list) return;
                toggle.addEventListener('click', function () {
                    var isHidden = list.classList.toggle('hidden');
                    toggle.setAttribute('aria-expanded', String(!isHidden));
                });
            })();
        </script>
    @endpush
@endsection
