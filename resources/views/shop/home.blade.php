@extends('shop.layouts.app')

@section('title', 'Elephant Spices — High Quality Spices')
@section('meta_description', 'Leading exporter & supplier of high quality spices. Pure, lab-tested, hygienically packed.')

@section('content')
@php
    $hero = $sections->get('hero');
    $why = $sections->get('why');
    $story = $sections->get('story');
    $process = $sections->get('process');
    $collections = $sections->get('collections');
    $testimonials = $sections->get('testimonials');
    $newsletter = $sections->get('newsletter');
    $heroTitle = data_get($hero?->content, 'headline', 'Leading Exporter & Supplier Of High Quality Spices.');
    $heroTitleHtml = preg_replace('/\b(Exporter|Supplier)\b/i', '<em>$1</em>', e($heroTitle));
@endphp

{{-- Hero / slider matching design mock --}}
<section class="spice-hero" data-hero-slider aria-label="Featured banners">
    <div class="spice-hero__inner">
        <div>
            <p class="spice-hero__eyebrow">{{ data_get($hero?->content, 'brand', 'Elephant Spices') }}</p>
            <h1 class="spice-hero__title">{!! $heroTitleHtml !!}</h1>
            <p class="spice-hero__sub">{{ data_get($hero?->content, 'subline', 'Authentic Indian spices sourced from renowned spice lands — fresh aroma, pure colour, uncompromised taste.') }}</p>
            <div class="spice-hero__actions">
                <a href="{{ data_get($hero?->content, 'cta_url', route('shop.catalog')) }}" class="btn-brand">
                    {{ data_get($hero?->content, 'cta_label', 'Visit Our Shop') }}
                </a>
            </div>
        </div>

        <div class="spice-hero__visual">
            @forelse($slides as $index => $slide)
                <div class="spice-hero__slide{{ $index === 0 ? ' is-active' : '' }}" data-slide-index="{{ $index }}" @if($index !== 0) aria-hidden="true" @endif>
                    @if($slide->link_url)
                        <a href="{{ $slide->link_url }}" class="absolute inset-0 block" aria-label="{{ $slide->title ?: 'Banner '.($index + 1) }}">
                            <picture>
                                @if($slide->mobile_image)
                                    <source media="(max-width: 768px)" srcset="{{ $slide->mobileImageUrl() }}">
                                @endif
                                <img src="{{ $slide->imageUrl() }}" alt="{{ $slide->title ?: 'Spice banner' }}" @if($index === 0) fetchpriority="high" @else loading="lazy" @endif>
                            </picture>
                        </a>
                    @else
                        <picture>
                            @if($slide->mobile_image)
                                <source media="(max-width: 768px)" srcset="{{ $slide->mobileImageUrl() }}">
                            @endif
                            <img src="{{ $slide->imageUrl() }}" alt="{{ $slide->title ?: 'Spice banner' }}" @if($index === 0) fetchpriority="high" @else loading="lazy" @endif>
                        </picture>
                    @endif
                </div>
            @empty
                <div class="spice-hero__slide is-active">
                    <img src="{{ data_get($hero?->content, 'image', 'https://images.unsplash.com/photo-1596040033229-a9821ebd058d?w=1200&q=80') }}" alt="Premium spices">
                </div>
            @endforelse

            @if($slides->count() > 1)
                <button type="button" class="spice-hero__nav spice-hero__nav--prev" data-slider-prev aria-label="Previous slide">‹</button>
                <button type="button" class="spice-hero__nav spice-hero__nav--next" data-slider-next aria-label="Next slide">›</button>
                <div class="spice-hero__dots" role="tablist" aria-label="Slide pagination">
                    @foreach($slides as $index => $slide)
                        <button type="button"
                                class="spice-hero__dot{{ $index === 0 ? ' is-active' : '' }}"
                                data-slider-dot="{{ $index }}"
                                role="tab"
                                aria-label="Go to slide {{ $index + 1 }}"
                                aria-selected="{{ $index === 0 ? 'true' : 'false' }}"></button>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</section>

{{-- Shop by Category --}}
@if(($sections->get('categories')?->is_enabled ?? true) && $categories->isNotEmpty())
<section class="section-pad">
    <div class="max-w-6xl mx-auto px-4 sm:px-6">
        <h2 class="section-title">{{ $sections->get('categories')?->title ?: 'Shop by Category' }}</h2>
        <p class="section-sub">{{ data_get($sections->get('categories')?->content, 'subline', 'Explore our pure and blended spices crafted for everyday cooking and festive feasts.') }}</p>
        <div class="cat-rail">
            @foreach($categories as $category)
                <a href="{{ route('shop.catalog', ['category' => $category->slug]) }}" class="cat-circle">
                    <img
                        class="cat-circle__img"
                        src="{{ $category->imageUrl() ?: 'https://images.unsplash.com/photo-1596040033229-a9821ebd058d?w=300&h=300&fit=crop&q=80' }}"
                        alt="{{ $category->name }}"
                        loading="lazy"
                    >
                    <span class="cat-circle__name">{{ $category->name }}</span>
                </a>
            @endforeach
        </div>
    </div>
</section>
@endif

{{-- Best Selling Products --}}
@if(($sections->get('featured')?->is_enabled ?? true))
<section class="section-pad bg-white/60 border-y border-[var(--line)]">
    <div class="max-w-6xl mx-auto px-4 sm:px-6">
        <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-3 mb-8">
            <div>
                <h2 class="section-title !text-left">{{ $sections->get('featured')?->title ?: 'Best Selling Products' }}</h2>
                <p class="mt-2 text-stone-500 text-sm max-w-xl">{{ data_get($sections->get('featured')?->content, 'subline', 'Customer favourites — authentic, fresh and pure.') }}</p>
            </div>
            <a href="{{ route('shop.catalog') }}" class="text-sm font-semibold text-brand hover:underline">View all</a>
        </div>
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-5">
            @foreach($featured as $product)
                @include('shop.partials.product-card', ['product' => $product])
            @endforeach
        </div>
    </div>
</section>
@endif

{{-- Why Elephant Spices --}}
@if($why?->is_enabled && !empty(data_get($why->content, 'items')))
<section class="section-pad">
    <div class="max-w-6xl mx-auto px-4 sm:px-6">
        <h2 class="section-title">{{ $why->title ?: 'Why Elephant Spices' }}</h2>
        <p class="section-sub">{{ data_get($why->content, 'subline', 'Quality you can taste in every dish.') }}</p>
        <div class="why-grid">
            @foreach(data_get($why->content, 'items', []) as $item)
                <div class="why-card">
                    <div class="why-card__icon">{{ $item['icon'] ?? '✦' }}</div>
                    <p class="why-card__title">{{ $item['title'] ?? '' }}</p>
                </div>
            @endforeach
        </div>
    </div>
</section>
@endif

{{-- Brand story --}}
@if($story?->is_enabled)
<section class="section-pad bg-white/50">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 grid lg:grid-cols-2 gap-8 lg:gap-12 items-center">
        <div class="rounded-2xl overflow-hidden min-h-[240px] sm:min-h-[320px] bg-cream-dark shadow-lg shadow-stone-900/5">
            <img src="{{ data_get($story->content, 'image', 'https://images.unsplash.com/photo-1596040033229-a9821ebd058d?w=1000&q=80') }}" alt="Elephant Spices packaging" class="w-full h-full object-cover min-h-[240px] sm:min-h-[320px]" loading="lazy">
        </div>
        <div>
            <h2 class="font-display text-2xl sm:text-3xl text-stone-900 leading-snug">{{ data_get($story->content, 'headline', $story->title) }}</h2>
            <p class="mt-4 text-stone-600 leading-relaxed">{{ data_get($story->content, 'body') }}</p>
            @if(!empty(data_get($story->content, 'bullets')))
                <ul class="mt-5 space-y-2.5">
                    @foreach(data_get($story->content, 'bullets', []) as $bullet)
                        <li class="flex gap-2.5 text-sm text-stone-700">
                            <span class="text-gold font-bold">●</span>
                            <span>{{ $bullet }}</span>
                        </li>
                    @endforeach
                </ul>
            @endif
            <div class="mt-7">
                <a href="{{ data_get($story->content, 'cta_url', route('shop.page', 'about-us')) }}" class="btn-outline-brand">
                    {{ data_get($story->content, 'cta_label', 'Read More') }}
                </a>
            </div>
        </div>
    </div>
</section>
@endif

{{-- Manufacturing process --}}
@if($process?->is_enabled && !empty(data_get($process->content, 'steps')))
<section class="section-pad">
    <div class="max-w-6xl mx-auto px-4 sm:px-6">
        <h2 class="section-title">{{ $process->title ?: 'Manufacturing Process' }}</h2>
        <p class="section-sub">{{ data_get($process->content, 'subline', 'From farm to pack — care at every step.') }}</p>
        <div class="process-track">
            @foreach(data_get($process->content, 'steps', []) as $i => $step)
                <div class="process-step">
                    <div class="process-step__num">{{ $i + 1 }}</div>
                    <p class="process-step__label">{{ $step['title'] ?? $step }}</p>
                </div>
            @endforeach
        </div>
    </div>
</section>
@endif

{{-- Featured collections --}}
@if($collections?->is_enabled && !empty(data_get($collections->content, 'items')))
<section id="collections" class="section-pad bg-white/60 border-y border-[var(--line)]">
    <div class="max-w-6xl mx-auto px-4 sm:px-6">
        <h2 class="section-title">{{ $collections->title ?: 'Featured Collections' }}</h2>
        <p class="section-sub">{{ data_get($collections->content, 'subline', 'Powder, masala, whole spices and more.') }}</p>
        <div class="mt-8 grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-5">
            @foreach(data_get($collections->content, 'items', []) as $item)
                <a href="{{ $item['url'] ?? route('shop.catalog') }}" class="collection-card">
                    <img src="{{ $item['image'] ?? '' }}" alt="{{ $item['title'] ?? '' }}" loading="lazy">
                    <span class="collection-card__label">{{ $item['title'] ?? '' }}</span>
                </a>
            @endforeach
        </div>
    </div>
</section>
@endif

{{-- Testimonials --}}
@if($testimonials?->is_enabled && !empty(data_get($testimonials->content, 'items')))
<section class="section-pad">
    <div class="max-w-6xl mx-auto px-4 sm:px-6">
        <h2 class="section-title">{{ $testimonials->title ?: 'What Our Customers Say' }}</h2>
        <div class="testimonial-rail">
            @foreach(data_get($testimonials->content, 'items', []) as $item)
                <article class="testimonial-card">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-full bg-cream-dark flex items-center justify-center text-brand font-bold text-sm">
                            {{ strtoupper(substr($item['name'] ?? 'C', 0, 1)) }}
                        </div>
                        <div>
                            <p class="font-semibold text-sm">{{ $item['name'] ?? '' }}</p>
                            <p class="testimonial-card__stars">★★★★★</p>
                        </div>
                    </div>
                    <p class="testimonial-card__quote">“{{ $item['quote'] ?? '' }}”</p>
                    @if(!empty($item['badge']))
                        <span class="inline-flex mt-4 text-xs font-semibold px-2.5 py-1 rounded-full bg-emerald-100 text-emerald-800">{{ $item['badge'] }}</span>
                    @endif
                </article>
            @endforeach
        </div>
    </div>
</section>
@endif

{{-- Newsletter --}}
@if($newsletter?->is_enabled ?? true)
<section class="section-pad pt-0">
    <div class="max-w-6xl mx-auto px-4 sm:px-6">
        <div class="newsletter-band">
            <div>
                <h2 class="newsletter-band__title">{{ data_get($newsletter?->content, 'headline', 'Join Our Kitchen Family') }}</h2>
                <p class="mt-2 text-white/80 text-sm max-w-md">{{ data_get($newsletter?->content, 'subline', 'Subscribe for recipes, offers and new spice drops.') }}</p>
            </div>
            <form class="newsletter-band__form" onsubmit="event.preventDefault(); if(window.AppAjax){ AppAjax.toast('Thanks for subscribing!', 'success'); }">
                <input type="email" name="email" required placeholder="Enter your email" aria-label="Email">
                <button type="submit" class="btn-gold">Subscribe</button>
            </form>
        </div>
    </div>
</section>
@endif
@endsection

@push('scripts')
<script src="{{ asset('js/hero-slider.js') }}" defer></script>
@endpush
