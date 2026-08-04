@extends('shop.layouts.app')

@section('title', 'Contact Us — '.config('app.name'))
@section('meta_description', 'Get in touch with Elephant Spices for wholesale, retail or general enquiries.')

@section('content')
<x-shop.breadcrumb title="Contact Us" />

<section class="py-14 sm:py-20 bg-cream">
    <div class="max-w-6xl mx-auto px-4 sm:px-6">
    <p class="text-stone-600 leading-relaxed max-w-2xl mx-auto text-center mb-12">Have a question about an order, a wholesale enquiry, or just want to say hello? Send us a message and our team will get back to you shortly.</p>

    <div class="grid lg:grid-cols-5 gap-6 lg:gap-0 rounded-3xl overflow-hidden shadow-xl shadow-stone-900/10">
        <div class="lg:col-span-2 bg-brand text-white p-8 sm:p-10 relative overflow-hidden">
            <div class="absolute -right-10 -top-10 w-40 h-40 rounded-full bg-white/5"></div>
            <div class="absolute -right-4 bottom-10 w-24 h-24 rounded-full bg-gold/10"></div>

            <p class="font-display text-2xl mb-2 relative">Let's talk spices</p>
            <p class="text-white/70 text-sm mb-8 relative">Reach out for orders, wholesale enquiries, or just to say hello.</p>

            <div class="space-y-6 relative">
                <div class="flex items-start gap-3">
                    <span class="shrink-0 w-10 h-10 rounded-full bg-white/10 flex items-center justify-center">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
                    </span>
                    <div>
                        <p class="text-xs uppercase tracking-wide text-white/60 mb-0.5">Phone</p>
                        <a href="tel:9876543210" class="font-medium hover:text-gold transition-colors">9876543210</a>
                    </div>
                </div>
                <div class="flex items-start gap-3">
                    <span class="shrink-0 w-10 h-10 rounded-full bg-white/10 flex items-center justify-center">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M4 4h16v16H4z" opacity="0"/><path d="m3 6 9 6 9-6"/><rect x="3" y="5" width="18" height="14" rx="2"/></svg>
                    </span>
                    <div>
                        <p class="text-xs uppercase tracking-wide text-white/60 mb-0.5">Email</p>
                        <a href="mailto:info@elephantspices.com" class="font-medium hover:text-gold transition-colors break-all">info@elephantspices.com</a>
                    </div>
                </div>
                <div class="flex items-start gap-3">
                    <span class="shrink-0 w-10 h-10 rounded-full bg-white/10 flex items-center justify-center">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/></svg>
                    </span>
                    <div>
                        <p class="text-xs uppercase tracking-wide text-white/60 mb-0.5">Address</p>
                        <p class="font-medium">India</p>
                    </div>
                </div>
                <div class="flex items-start gap-3">
                    <span class="shrink-0 w-10 h-10 rounded-full bg-white/10 flex items-center justify-center">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 3"/></svg>
                    </span>
                    <div>
                        <p class="text-xs uppercase tracking-wide text-white/60 mb-0.5">Hours</p>
                        <p class="font-medium">Mon – Sat, 9:00 AM – 6:00 PM IST</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="lg:col-span-3 bg-white p-8 sm:p-10">
            <p class="font-display text-2xl text-stone-900 mb-1">Send us a message</p>
            <p class="text-stone-500 text-sm mb-6">We usually reply within one business day.</p>

            <form method="POST" action="{{ route('shop.contact.store') }}" class="space-y-4">
                @csrf

                <div>
                    <label for="name" class="block text-sm mb-1 text-stone-700">Name</label>
                    <input id="name" type="text" name="name" value="{{ old('name') }}" required
                           class="w-full rounded-lg border border-stone-300 px-3.5 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-brand/40 focus:border-brand transition-colors">
                    @error('name')<p class="text-rose-600 text-xs mt-1">{{ $message }}</p>@enderror
                </div>

                <div class="grid sm:grid-cols-2 gap-4">
                    <div>
                        <label for="email" class="block text-sm mb-1 text-stone-700">Email</label>
                        <input id="email" type="email" name="email" value="{{ old('email') }}" required
                               class="w-full rounded-lg border border-stone-300 px-3.5 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-brand/40 focus:border-brand transition-colors">
                        @error('email')<p class="text-rose-600 text-xs mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="phone" class="block text-sm mb-1 text-stone-700">Phone <span class="text-stone-400">(optional)</span></label>
                        <input id="phone" type="text" name="phone" value="{{ old('phone') }}"
                               class="w-full rounded-lg border border-stone-300 px-3.5 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-brand/40 focus:border-brand transition-colors">
                        @error('phone')<p class="text-rose-600 text-xs mt-1">{{ $message }}</p>@enderror
                    </div>
                </div>

                <div>
                    <label for="message" class="block text-sm mb-1 text-stone-700">Message</label>
                    <textarea id="message" name="message" rows="5" required
                              class="w-full rounded-lg border border-stone-300 px-3.5 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-brand/40 focus:border-brand transition-colors">{{ old('message') }}</textarea>
                    @error('message')<p class="text-rose-600 text-xs mt-1">{{ $message }}</p>@enderror
                </div>

                <button type="submit" class="w-full sm:w-auto rounded-full bg-brand hover:bg-brand-dark text-white font-semibold px-8 py-3 text-sm transition-colors shadow-lg shadow-brand/20">
                    Send Message
                </button>
            </form>
        </div>
    </div>
    </div>
</section>
@endsection
