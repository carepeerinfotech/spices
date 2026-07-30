@extends('shop.layouts.app')
@section('title', 'Forgot password')
@section('content')
<div class="max-w-md mx-auto px-4 py-12">
    <h1 class="font-display text-3xl mb-6">Forgot password</h1>
    <form data-ajax method="POST" action="{{ route('password.email') }}" class="rounded-xl border border-slate-200 bg-white p-6 space-y-4">
        @csrf
        <div>
            <label class="text-sm">Email</label>
            <input type="email" name="email" required class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2">
        </div>
        <button class="w-full rounded-lg bg-brand text-white py-2.5 text-sm">Email reset link</button>
    </form>
    <p class="text-sm text-slate-500 mt-4"><a href="{{ route('login') }}" class="text-brand">Back to login</a></p>
</div>
@endsection
