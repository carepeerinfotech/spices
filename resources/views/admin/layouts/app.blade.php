<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Admin') — {{ config('app.name') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
     <link rel="icon" type="image/x-icon" href="assets/images/elephant-fav.png">
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,400;0,9..40,500;0,9..40,600;0,9..40,700;1,9..40,400&family=Fraunces:opsz,wght@9..144,600;9..144,700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        brand: { DEFAULT: '#0f766e', dark: '#0d5c56', light: '#14b8a6' },
                        ink: '#0f172a'
                    },
                    fontFamily: {
                        sans: ['DM Sans', 'ui-sans-serif', 'system-ui', 'sans-serif'],
                        display: ['Fraunces', 'Georgia', 'serif']
                    }
                }
            }
        }
    </script>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    @stack('styles')
</head>
<body class="bg-slate-50 text-ink antialiased">
<div class="admin-shell">
    <aside class="admin-sidebar p-5">
        <div class="mb-8">
            <a href="{{ route('admin.dashboard') }}" class="font-display text-2xl text-white tracking-tight">Elephant</a>
            <p class="text-teal-200/80 text-xs mt-1 uppercase tracking-widest">Admin Panel</p>
        </div>
        <nav class="admin-nav space-y-1 text-sm">
            <a href="{{ route('admin.dashboard') }}" class="{{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">Dashboard</a>
            <a href="{{ route('admin.orders.index') }}" class="{{ request()->routeIs('admin.orders.*') ? 'active' : '' }}">Orders</a>
            <a href="{{ route('admin.products.index') }}" class="{{ request()->routeIs('admin.products.*') ? 'active' : '' }}">Products</a>
            <a href="{{ route('admin.categories.index') }}" class="{{ request()->routeIs('admin.categories.*') ? 'active' : '' }}">Categories</a>
            <a href="{{ route('admin.pages.index') }}" class="{{ request()->routeIs('admin.pages.*') ? 'active' : '' }}">CMS Pages</a>
            <a href="{{ route('admin.contact-messages.index') }}" class="{{ request()->routeIs('admin.contact-messages.*') ? 'active' : '' }}">Contact Messages</a>
            <a href="{{ route('admin.homepage.edit') }}" class="{{ request()->routeIs('admin.homepage.*') ? 'active' : '' }}">Homepage</a>
            <a href="{{ route('admin.homepage-slides.index') }}" class="{{ request()->routeIs('admin.homepage-slides.*') ? 'active' : '' }}">Hero Slider</a>
            <a href="{{ route('admin.email-templates.index') }}" class="{{ request()->routeIs('admin.email-templates.*') ? 'active' : '' }}">Email templates</a>
            <a href="{{ route('admin.settings.index') }}" class="{{ request()->routeIs('admin.settings.*') ? 'active' : '' }}">Settings</a>
            <a href="{{ route('admin.users.index') }}" class="{{ request()->routeIs('admin.users.*') ? 'active' : '' }}">Users</a>
            <a href="{{ route('admin.roles.index') }}" class="{{ request()->routeIs('admin.roles.*') ? 'active' : '' }}">Roles</a>
            <a href="{{ route('shop.home') }}" target="_blank" rel="noopener">View Store ↗</a>
        </nav>
        <form method="POST" action="{{ route('admin.logout') }}" class="mt-10">
            @csrf
            <button type="submit" class="w-full text-left text-sm text-rose-200 hover:text-white px-3 py-2">Sign out</button>
        </form>
    </aside>

    <div class="min-w-0">
        <header class="bg-white border-b border-slate-200 px-6 py-4 flex items-center justify-between">
            <div>
                <h1 class="font-display text-xl text-ink">@yield('heading', 'Dashboard')</h1>
                @hasSection('subtitle')
                    <p class="text-sm text-slate-500 mt-0.5">@yield('subtitle')</p>
                @endif
            </div>
            <div class="text-sm text-slate-600">
                {{ auth()->user()->name }}
            </div>
        </header>

        <main class="p-6">
            @if(session('success'))
                <div class="mb-4 rounded-lg bg-emerald-50 text-emerald-800 border border-emerald-200 px-4 py-3 text-sm">{{ session('success') }}</div>
            @endif
            @if(session('error'))
                <div class="mb-4 rounded-lg bg-rose-50 text-rose-800 border border-rose-200 px-4 py-3 text-sm">{{ session('error') }}</div>
            @endif
            @yield('content')
        </main>
    </div>
</div>
<script src="{{ asset('js/app-ajax.js') }}" defer></script>
@stack('scripts')
</body>
</html>
