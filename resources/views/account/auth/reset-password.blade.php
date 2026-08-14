@extends('shop.layouts.app')
@section('title', 'Reset password')
@section('content')
<div class="max-w-md mx-auto px-4 py-12">
    <h1 class="font-display text-3xl mb-6">Reset password</h1>
    <form data-ajax method="POST" action="{{ route('password.update') }}" class="rounded-xl border border-slate-200 bg-white p-6 space-y-4">
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">
        <div>
            <label class="text-sm">Email</label>
            {{-- Readonly rather than disabled: a disabled field is not submitted,
                 and the reset is validated against this address. --}}
            <input type="email" name="email" value="{{ $email }}" required readonly
                   class="mt-1 w-full rounded-lg border border-slate-300 bg-slate-100 text-slate-500 cursor-not-allowed px-3 py-2">
        </div>
        <div>
            <label class="text-sm">New password</label>
            <input type="password" name="password" required class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2">
        </div>
        <div>
            <label class="text-sm">Confirm password</label>
            <input type="password" name="password_confirmation" required class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2">
        </div>
        <button class="w-full rounded-lg bg-brand text-white py-2.5 text-sm">Reset password</button>
    </form>
</div>
@endsection
