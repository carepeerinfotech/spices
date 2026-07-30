@extends('shop.layouts.app')
@section('title', 'Verify email')
@section('content')
<div class="max-w-md mx-auto px-4 py-16 text-center">
    <h1 class="font-display text-3xl mb-4">Verify your email</h1>
    <p class="text-slate-600 mb-6 text-sm">We sent a verification link to your email. Verified customers can checkout.</p>
    <form data-ajax method="POST" action="{{ route('verification.send') }}">
        @csrf
        <button class="rounded-lg bg-brand text-white px-4 py-2 text-sm">Resend verification email</button>
    </form>
</div>
@endsection
