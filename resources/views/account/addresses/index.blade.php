@extends('shop.layouts.app')
@section('title', 'Addresses')
@section('content')
<div class="max-w-3xl mx-auto px-4 py-10 space-y-6">
    <h1 class="font-display text-3xl">Shipping addresses</h1>

    <form data-ajax method="POST" action="{{ route('account.addresses.store') }}" class="rounded-xl border bg-white p-5 grid sm:grid-cols-2 gap-3">
        @csrf
        <input name="label" placeholder="Label (Home)" class="rounded-lg border px-3 py-2 text-sm">
        <input name="name" placeholder="Full name" required class="rounded-lg border px-3 py-2 text-sm">
        <input name="phone" placeholder="Phone" required class="rounded-lg border px-3 py-2 text-sm">
        <input name="email" type="email" placeholder="Email" class="rounded-lg border px-3 py-2 text-sm">
        <input name="address_line1" placeholder="Address line 1" required class="sm:col-span-2 rounded-lg border px-3 py-2 text-sm">
        <input name="address_line2" placeholder="Address line 2" class="sm:col-span-2 rounded-lg border px-3 py-2 text-sm">
        <input name="city" placeholder="City" required class="rounded-lg border px-3 py-2 text-sm">
        <input name="state" placeholder="State" required class="rounded-lg border px-3 py-2 text-sm">
        <input name="postal_code" placeholder="Pincode" required class="rounded-lg border px-3 py-2 text-sm">
        <input name="country" value="IN" class="rounded-lg border px-3 py-2 text-sm">
        <label class="text-sm inline-flex items-center gap-2"><input type="checkbox" name="is_default_shipping" value="1" data-bool> Default shipping</label>
        <label class="text-sm inline-flex items-center gap-2"><input type="checkbox" name="is_default_billing" value="1" data-bool> Default billing</label>
        <button class="sm:col-span-2 rounded-lg bg-brand text-white py-2 text-sm">Save address</button>
    </form>

    <div class="space-y-3">
        @foreach($addresses as $address)
            <div class="rounded-xl border bg-white p-4 text-sm flex justify-between gap-4">
                <div>
                    <p class="font-medium">{{ $address->label }} — {{ $address->name }}</p>
                    <p class="text-slate-600">{{ $address->fullAddress() }}</p>
                    <p class="text-slate-500">{{ $address->phone }}</p>
                </div>
                <button type="button" data-delete="{{ route('account.addresses.destroy', $address) }}" class="text-rose-600">Delete</button>
            </div>
        @endforeach
    </div>
</div>
@endsection
