@extends('shop.layouts.app')
@section('title', 'Login')
@section('content')
<div class="max-w-md mx-auto px-4 py-12">
    <h1 class="font-display text-3xl mb-6">Sign in</h1>
    <form data-ajax method="POST" action="{{ route('login.submit') }}" class="rounded-xl border border-slate-200 bg-white p-6 space-y-4">
        @csrf
        <div>
            <label class="text-sm">Email</label>
            <input type="email" name="email" required class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2">
        </div>
        <div>
            <label class="text-sm">Password</label>
            <input type="password" name="password" required class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2">
        </div>
        <label class="inline-flex items-center gap-2 text-sm"><input type="checkbox" name="remember" value="1"> Remember me</label>
        <button class="w-full rounded-lg bg-brand text-white py-2.5 text-sm">Sign in</button>
    </form>
    <p class="text-sm text-slate-500 mt-5 register-btn-login">No account? <a href="{{ route('register') }}" class="text-brand">Register</a></p>
    <p class="text-sm text-slate-500 mt-2"><a href="{{ route('password.request') }}" class="text-brand">Forgot password?</a></p>
</div>
@endsection
