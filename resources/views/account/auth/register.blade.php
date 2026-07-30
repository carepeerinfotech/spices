@extends('shop.layouts.app')
@section('title', 'Register')
@section('content')
<div class="max-w-md mx-auto px-4 py-12">
    <h1 class="font-display text-3xl mb-6">Create account</h1>
    <form data-ajax method="POST" action="{{ route('register.submit') }}" class="rounded-xl border border-slate-200 bg-white p-6 space-y-4">
        @csrf
        <div><label class="text-sm">Name</label><input name="name" required class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2"></div>
        <div><label class="text-sm">Email</label><input type="email" name="email" required class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2"></div>
        <div><label class="text-sm">Phone</label><input name="phone" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2"></div>
        <div><label class="text-sm">Password</label><input type="password" name="password" required class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2"></div>
        <div><label class="text-sm">Confirm password</label><input type="password" name="password_confirmation" required class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2"></div>
        <button class="w-full rounded-lg bg-brand text-white py-2.5 text-sm">Create account</button>
    </form>
</div>
@endsection
