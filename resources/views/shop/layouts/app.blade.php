<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', config('app.name'))</title>
    <meta name="description" content="@yield('meta_description', 'Elephant Spices — leading exporter & supplier of high quality spices.')">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Libre+Baskerville:wght@400;700&family=Outfit:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        brand: { DEFAULT: '#7a1f2b', dark: '#5c1520', light: '#9b2d3a' },
                        gold: { DEFAULT: '#c9a227', dark: '#a8841a' },
                        cream: { DEFAULT: '#fbf6ee', dark: '#f3ead9' }
                    },
                    fontFamily: {
                        sans: ['Outfit', 'ui-sans-serif', 'system-ui', 'sans-serif'],
                        display: ['Libre Baskerville', 'Georgia', 'serif']
                    }
                }
            }
        }
    </script>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    @stack('styles')
</head>
<body class="bg-cream text-stone-900 antialiased min-h-screen flex flex-col">
<header class="site-header {{ request()->routeIs('shop.home') ? 'site-header--overlay' : 'site-header--solid' }}"
        @if(request()->routeIs('shop.home')) data-transparent-header @endif
        data-mobile-header>
    <div class="max-w-6xl mx-auto px-4 sm:px-6 h-16 md:h-[4.25rem] flex items-center justify-between gap-3">
        <a href="{{ route('shop.home') }}" class="site-header__brand font-display text-xl sm:text-2xl tracking-tight">Elephant Spices</a>

        <nav class="site-header__nav hidden lg:flex items-center gap-5 xl:gap-6">
            <a href="{{ route('shop.page', 'about-us') }}">About</a>
            <a href="{{ route('shop.catalog') }}">Products</a>
            <a href="{{ route('shop.catalog') }}#collections">New Collections</a>
            <a href="{{ route('shop.page', 'about-us') }}">Brand</a>
            <a href="{{ route('shop.page', 'privacy-policy') }}">Policy</a>
            <a href="{{ route('shop.page', 'contact') }}">Contact</a>
        </nav>

        <div class="flex items-center gap-2 sm:gap-3">
            <a href="{{ route('shop.catalog') }}" class="site-header__link hidden sm:inline-flex" aria-label="Search products">Search</a>
            @auth
                <a href="{{ route('account.dashboard') }}" class="site-header__link hidden sm:inline-flex">Account</a>
            @else
                <a href="{{ route('login') }}" class="site-header__link hidden sm:inline-flex">Login</a>
            @endauth
            <a href="{{ route('shop.cart') }}" class="site-header__link relative inline-flex items-center gap-1.5 font-semibold">
                Bag
                <span data-cart-count class="inline-flex min-w-[1.25rem] h-5 items-center justify-center rounded-full bg-brand text-white text-xs px-1.5" @if(($cartCount ?? 0) < 1) hidden @endif>{{ $cartCount ?? 0 }}</span>
            </a>
            <button type="button" class="site-header__menu-btn" data-menu-toggle aria-label="Open menu" aria-expanded="false">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 7h16M4 12h16M4 17h16"/></svg>
            </button>
        </div>
    </div>
    <div class="site-header__mobile max-w-6xl mx-auto px-4 sm:px-6 lg:hidden">
        <a href="{{ route('shop.page', 'about-us') }}">About</a>
        <a href="{{ route('shop.catalog') }}">Products</a>
        <a href="{{ route('shop.catalog') }}">New Collections</a>
        <a href="{{ route('shop.page', 'about-us') }}">Brand</a>
        <a href="{{ route('shop.page', 'privacy-policy') }}">Policy</a>
        <a href="{{ route('shop.page', 'contact') }}">Contact</a>
        @auth
            <a href="{{ route('account.dashboard') }}">Account</a>
        @else
            <a href="{{ route('login') }}">Login</a>
        @endauth
    </div>
</header>

@if(session('error'))
    <div class="bg-rose-50 text-rose-800 text-sm text-center py-2 border-b border-rose-100">{{ session('error') }}</div>
@endif
@if(session('success'))
    <div class="bg-emerald-50 text-emerald-800 text-sm text-center py-2 border-b border-emerald-100">{{ session('success') }}</div>
@endif

<main class="flex-1 page-shell">
    @yield('content')
</main>

<footer class="site-footer">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 py-12 grid sm:grid-cols-2 lg:grid-cols-4 gap-8 text-sm">
        <div class="lg:col-span-1">
            <p class="site-footer__brand mb-3">Elephant Spices</p>
            <p class="text-stone-400 max-w-xs leading-relaxed">Three generations of flavour since 1974. Pure, lab-tested spices for kitchens worldwide.</p>
        </div>
        <div>
            <p class="text-white font-semibold mb-3">Company</p>
            <div class="space-y-2">
                <a class="block" href="{{ route('shop.page', 'about-us') }}">About us</a>
                <a class="block" href="{{ route('shop.page', 'contact') }}">Contact</a>
                <a class="block" href="{{ route('admin.login') }}">Staff login</a>
            </div>
        </div>
        <div>
            <p class="text-white font-semibold mb-3">Products</p>
            <div class="space-y-2">
                <a class="block" href="{{ route('shop.catalog') }}">All spices</a>
                <a class="block" href="{{ route('shop.catalog', ['category' => 'turmeric']) }}">Turmeric</a>
                <a class="block" href="{{ route('shop.catalog', ['category' => 'garam-masala']) }}">Garam Masala</a>
            </div>
        </div>
        <div>
            <p class="text-white font-semibold mb-3">Policies</p>
            <div class="space-y-2">
                <a class="block" href="{{ route('shop.page', 'shipping-policy') }}">Shipping</a>
                <a class="block" href="{{ route('shop.page', 'privacy-policy') }}">Privacy</a>
            </div>
        </div>
    </div>
    <div class="border-t border-white/10">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 py-4 flex flex-col sm:flex-row gap-2 sm:items-center sm:justify-between text-xs text-stone-500">
            <p>© {{ date('Y') }} Elephant Spices. All rights reserved.</p>
            <p>Pure · Lab Tested · Hygienically Packed</p>
        </div>
    </div>
</footer>

<script src="{{ asset('js/app-ajax.js') }}" defer></script>
<script src="{{ asset('js/navbar.js') }}" defer></script>
@stack('scripts')
</body>
</html>
