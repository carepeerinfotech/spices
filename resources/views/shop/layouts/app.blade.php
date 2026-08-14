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
    <link href="https://fonts.googleapis.com/css2?family=Libre+Baskerville:wght@400;700&family=Outfit:wght@400;500;600;700&family=DM+Sans:wght@400;500;600;700&family=Playfair+Display:wght@600;700;800&display=swap" rel="stylesheet">
     <link rel="icon" type="image/x-icon" href="assets/images/elephant-fav.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.3.0/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        brand: { DEFAULT: '#b82125', dark: '#981518', light: '#d84348' },
                        gold: { DEFAULT: '#efa400', dark: '#c98700' },
                        cream: { DEFAULT: '#fff7ec', dark: '#f5ead9' }
                    },
                    fontFamily: {
                        sans: ['DM Sans', 'ui-sans-serif', 'system-ui', 'sans-serif'],
                        display: ['Montserrat', 'serif']
                    }
                }
            }
        }
    </script>
    <link rel="stylesheet" href="@asset('css/app.css')">
    <link rel="stylesheet" href="@asset('css/home.css')">
    @stack('styles')
</head>
<body class="text-stone-900 antialiased min-h-screen flex flex-col">
<x-home.header :cart-count="$cartCount ?? 0" />

@if(session('error'))
    <div class="bg-rose-50 text-rose-800 text-sm text-center py-2 border-b border-rose-100">{{ session('error') }}</div>
@endif
@if(session('success'))
    <div class="bg-emerald-50 text-emerald-800 text-sm text-center py-2 border-b border-emerald-100">{{ session('success') }}</div>
@endif

<main class="flex-1 page-shell">
    @yield('content')
</main>

<x-home.footer />

<script src="@asset('js/app-ajax.js')" defer></script>
<script src="@asset('js/home.js')" defer></script>
<script src="@asset('js/search.js')" defer></script>
<script src="@asset('js/cart-drawer.js')" defer></script>
<script src="@asset('js/product-variants.js')" defer></script>
@stack('scripts')
</body>
</html>
