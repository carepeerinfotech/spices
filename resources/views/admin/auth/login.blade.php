<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Admin Login — {{ config('app.name') }}</title>
     <link rel="icon" type="image/x-icon" href="assets/images/elephant-fav.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:opsz,wght@9..40,400;9..40,500;9..40,600;9..40,700&family=Fraunces:opsz,wght@9..144,600;9..144,700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: { brand: { DEFAULT: '#0f766e', dark: '#0d5c56' } },
                    fontFamily: {
                        sans: ['DM Sans', 'ui-sans-serif', 'system-ui', 'sans-serif'],
                        display: ['Fraunces', 'Georgia', 'serif']
                    }
                }
            }
        }
    </script>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body class="min-h-screen bg-slate-950 text-white antialiased">
<div class="min-h-screen grid lg:grid-cols-2">
    <div class="hidden lg:flex flex-col justify-between p-12 bg-gradient-to-br from-teal-900 via-slate-900 to-slate-950">
        <div>
            <p class="font-display text-4xl tracking-tight">Elephant</p>
            <p class="text-teal-200/80 mt-2 text-sm uppercase tracking-[0.2em]">Commerce Admin</p>
        </div>
        <div>
            <h2 class="font-display text-3xl leading-tight max-w-md">Run products, orders, and content from one calm workspace.</h2>
            <p class="mt-4 text-slate-300 max-w-sm">Modern Tailwind admin with lightweight AJAX — no npm build step required.</p>
        </div>
        <p class="text-slate-500 text-sm">© {{ date('Y') }} Elephant Shop</p>
    </div>

    <div class="flex items-center justify-center p-6 sm:p-10">
        <div class="w-full max-w-md">
            <h1 class="font-display text-3xl mb-2">Sign in</h1>
            <p class="text-slate-400 mb-8 text-sm">Use your admin credentials to continue.</p>

            <form data-ajax method="POST" action="{{ route('admin.login.submit') }}" class="space-y-5">
                @csrf
                <div>
                    <label class="block text-sm text-slate-300 mb-1.5" for="email">Email</label>
                    <input id="email" name="email" type="email" required value="{{ old('email', 'admin@elephant.com') }}"
                           class="w-full rounded-lg bg-slate-900 border border-slate-700 px-3 py-2.5 text-white focus:outline-none focus:ring-2 focus:ring-teal-500">
                </div>
                <div>
                    <label class="block text-sm text-slate-300 mb-1.5" for="password">Password</label>
                    <input id="password" name="password" type="password" required value="password"
                           class="w-full rounded-lg bg-slate-900 border border-slate-700 px-3 py-2.5 text-white focus:outline-none focus:ring-2 focus:ring-teal-500">
                </div>
                <label class="flex items-center gap-2 text-sm text-slate-400">
                    <input type="checkbox" name="remember" value="1" class="rounded border-slate-600 bg-slate-900 text-teal-600 focus:ring-teal-500">
                    Remember me
                </label>
                <button type="submit" data-loading="Signing in..."
                        class="w-full rounded-lg bg-teal-600 hover:bg-teal-500 text-white font-medium py-2.5 transition">
                    Sign in
                </button>
            </form>
            <p class="mt-6 text-xs text-slate-500">Default: admin@elephant.com / password</p>
        </div>
    </div>
</div>
<script src="{{ asset('js/app-ajax.js') }}" defer></script>
</body>
</html>
