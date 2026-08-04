@extends('shop.layouts.app')

@section('title', 'About Us — '.config('app.name'))
@section('meta_description', "The story behind Elephant Spices — pure, lab-tested spices sourced directly from India's spice-growing heartlands since 1974.")

@section('content')
<x-shop.breadcrumb title="About Us" />

<section class="py-14 sm:py-20">
    <div class="max-w-6xl mx-auto px-4 sm:px-6">
        <div class="text-center max-w-2xl mx-auto mb-10 sm:mb-14">
            <p class="text-brand text-xs font-semibold tracking-widest uppercase mb-2">Our Story</p>
            <h2 class="font-display text-2xl sm:text-3xl tracking-tight mb-3">Pure Spices, Honestly Sourced</h2>
            <p class="text-stone-600 leading-relaxed">Since 1974, Elephant Spices has been bringing authentic Indian flavours to kitchens across the country — one lab-tested batch at a time.</p>
        </div>
        <div class="rounded-2xl overflow-hidden shadow-md shadow-stone-900/5">
            <img src="{{ asset('assets/images/banner1.jpg') }}" alt="Elephant Spices product range — turmeric, mirch and garam masala" class="w-full h-auto" loading="lazy">
        </div>
    </div>
</section>

<section class="py-14 sm:py-20 bg-cream">
    <div class="max-w-6xl mx-auto px-4 sm:px-6">
        <div class="grid lg:grid-cols-2 gap-10 lg:gap-14 items-center">
            <div class="rounded-2xl overflow-hidden shadow-md shadow-stone-900/5 order-2 lg:order-1 aspect-[4/3]">
                <img src="{{ asset('assets/images/products.jpg') }}" alt="Elephant Spices products displayed in a kitchen setting" class="w-full h-full object-cover" loading="lazy">
            </div>
            <div class="order-1 lg:order-2">
                <p class="text-brand text-xs font-semibold tracking-widest uppercase mb-2">Since 1974</p>
                <h2 class="font-display text-2xl sm:text-3xl tracking-tight mb-4">Three generations of flavour</h2>
                <p class="text-stone-600 leading-relaxed mb-4">What began as a single family stall trading pepper and turmeric has grown into a trusted, ISO 9001:2008 certified name across India — without ever losing sight of what made us different in the first place: real spices, honestly sourced.</p>
                <p class="text-stone-600 leading-relaxed mb-6">We work directly with farmers across India's renowned spice-growing belts, so every batch that carries the Elephant name can be traced back to where it was grown, cleaned and packed under strict hygiene standards.</p>
                <div class="grid grid-cols-3 gap-4 text-center">
                    <div>
                        <p class="font-display text-2xl sm:text-3xl text-brand">1974</p>
                        <p class="text-xs text-stone-500 mt-1">Established</p>
                    </div>
                    <div>
                        <p class="font-display text-2xl sm:text-3xl text-brand">50+</p>
                        <p class="text-xs text-stone-500 mt-1">Years of trust</p>
                    </div>
                    <div>
                        <p class="font-display text-2xl sm:text-3xl text-brand">ISO</p>
                        <p class="text-xs text-stone-500 mt-1">9001:2008 certified</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="py-14 sm:py-20">
    <div class="max-w-6xl mx-auto px-4 sm:px-6">
        <div class="grid sm:grid-cols-2 gap-6">
            <div class="rounded-2xl bg-white border border-[var(--line)] p-8 shadow-sm">
                <div class="w-12 h-12 rounded-full bg-cream-dark text-brand flex items-center justify-center mb-4">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 2 2 7l10 5 10-5-10-5Z"/><path d="m2 17 10 5 10-5"/><path d="m2 12 10 5 10-5"/></svg>
                </div>
                <h3 class="font-display text-xl mb-2">Our Mission</h3>
                <p class="text-stone-600 leading-relaxed">To bring pure, lab-tested spices from India's finest growing regions straight to every kitchen — with no fillers, no artificial colours, and no shortcuts on quality.</p>
            </div>
            <div class="rounded-2xl bg-white border border-[var(--line)] p-8 shadow-sm">
                <div class="w-12 h-12 rounded-full bg-cream-dark text-brand flex items-center justify-center mb-4">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="12" r="3"/><path d="M2 12s4-7 10-7 10 7 10 7-4 7-10 7-10-7-10-7Z"/></svg>
                </div>
                <h3 class="font-display text-xl mb-2">Our Vision</h3>
                <p class="text-stone-600 leading-relaxed">To be the most trusted spice brand in every Indian kitchen — known as much for purity and traceability as for the flavour that has kept families coming back for three generations.</p>
            </div>
        </div>
    </div>
</section>

<section class="py-14 sm:py-20 bg-cream">
    <div class="max-w-6xl mx-auto px-4 sm:px-6">
        <div class="text-center mb-10">
            <h2 class="font-display text-2xl sm:text-3xl tracking-tight mb-3">What we stand for</h2>
            <p class="text-stone-500 max-w-xl mx-auto">The principles behind every packet that leaves our facility.</p>
        </div>
        <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-5">
            <div class="rounded-xl bg-white border border-[var(--line)] p-6 text-center shadow-sm">
                <p class="font-display text-lg mb-2">Purity First</p>
                <p class="text-sm text-stone-500 leading-relaxed">Every batch is lab-tested before it reaches your kitchen.</p>
            </div>
            <div class="rounded-xl bg-white border border-[var(--line)] p-6 text-center shadow-sm">
                <p class="font-display text-lg mb-2">Direct Sourcing</p>
                <p class="text-sm text-stone-500 leading-relaxed">We buy straight from farmers, cutting out middlemen who compromise on quality.</p>
            </div>
            <div class="rounded-xl bg-white border border-[var(--line)] p-6 text-center shadow-sm">
                <p class="font-display text-lg mb-2">Family Expertise</p>
                <p class="text-sm text-stone-500 leading-relaxed">Our blends are still built on recipes passed down within the family.</p>
            </div>
            <div class="rounded-xl bg-white border border-[var(--line)] p-6 text-center shadow-sm">
                <p class="font-display text-lg mb-2">Pan-India Reach</p>
                <p class="text-sm text-stone-500 leading-relaxed">From retail packs to bulk wholesale, we supply homes and businesses nationwide.</p>
            </div>
        </div>
    </div>
</section>

<section class="py-14 sm:py-20">
    <div class="max-w-6xl mx-auto px-4 sm:px-6">
        <div class="rounded-2xl bg-brand text-white text-center px-6 py-12 sm:py-16">
            <h2 class="font-display text-2xl sm:text-3xl mb-3">Taste the difference purity makes</h2>
            <p class="text-white/80 max-w-xl mx-auto mb-6">Explore our full range of pure powders, blended masalas, vrat atta and salt.</p>
            <a href="{{ route('shop.catalog') }}" class="btn-gold">Shop our spices</a>
        </div>
    </div>
</section>
@endsection
