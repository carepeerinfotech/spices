@extends('shop.layouts.app')

@section('title', 'Checkout — '.config('app.name'))

@section('content')
<x-shop.breadcrumb title="Checkout" />

<div class="max-w-5xl mx-auto px-4 sm:px-6 py-10">
    <div class="grid lg:grid-cols-5 gap-8">
        <form data-ajax method="POST" action="{{ route('shop.checkout.store') }}" class="lg:col-span-3 rounded-xl border border-slate-200 bg-white p-6 space-y-6" id="checkout-form">
            @csrf

            <div>
                <h2 class="font-medium text-lg mb-1">Billing details</h2>
                <p class="text-sm text-slate-500 mb-4">We'll use this as your delivery address unless you ship to a different one below.</p>

                <div class="grid sm:grid-cols-2 gap-4">
                    <div class="sm:col-span-2">
                        <label class="block text-sm mb-1">Full Name</label>
                        <input name="billing_name" required value="{{ old('billing_name', $address?->name ?: $user->name) }}"
                               class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                        @error('billing_name')<p class="text-rose-600 text-xs mt-1">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label class="block text-sm mb-1">Email address</label>
                        <input type="email" name="billing_email" required value="{{ old('billing_email', $address?->email ?: $user->email) }}"
                               class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                        @error('billing_email')<p class="text-rose-600 text-xs mt-1">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label class="block text-sm mb-1">Phone</label>
                        <input name="billing_phone" required value="{{ old('billing_phone', $address?->phone ?: $user->phone) }}"
                               class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                        @error('billing_phone')<p class="text-rose-600 text-xs mt-1">{{ $message }}</p>@enderror
                    </div>

                    <div class="sm:col-span-2">
                        <label class="block text-sm mb-1">Street Address</label>
                        <input name="billing_address_line1" required placeholder="House number and street name"
                               value="{{ old('billing_address_line1', $address?->address_line1) }}"
                               class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm mb-2">
                        <input name="billing_address_line2" placeholder="Apartment, suite, unit, etc. (optional)"
                               value="{{ old('billing_address_line2', $address?->address_line2) }}"
                               class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                        @error('billing_address_line1')<p class="text-rose-600 text-xs mt-1">{{ $message }}</p>@enderror
                    </div>


                    <div>
                        <label class="block text-sm mb-1">Country</label>
                        <select name="billing_country" required class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                            @foreach(config('countries') as $code => $name)
                                <option value="{{ $code }}" @selected(old('billing_country', 'IN') === $code)>{{ $name }}</option>
                            @endforeach
                        </select>
                        @error('billing_country')<p class="text-rose-600 text-xs mt-1">{{ $message }}</p>@enderror
                    </div>
                    
                    <div>
                        <label class="block text-sm mb-1">City</label>
                        <input name="billing_city" required value="{{ old('billing_city', $address?->city) }}"
                               class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                        @error('billing_city')<p class="text-rose-600 text-xs mt-1">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label class="block text-sm mb-1">State</label>
                        <input name="billing_state" required value="{{ old('billing_state', $address?->state) }}"
                               class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                        @error('billing_state')<p class="text-rose-600 text-xs mt-1">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label class="block text-sm mb-1">Pin code</label>
                        <input name="billing_postal_code" id="billing_postal_code" required maxlength="6" inputmode="numeric"
                               value="{{ old('billing_postal_code', $address?->postal_code) }}"
                               class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                        @error('billing_postal_code')<p class="text-rose-600 text-xs mt-1">{{ $message }}</p>@enderror
                    </div>

                    
                </div>
            </div>

            <label class="flex items-center gap-2 text-sm font-medium">
                <input type="checkbox" name="ship_to_different_address" value="1" data-bool id="ship-different" {{ old('ship_to_different_address') ? 'checked' : '' }}>
                Ship to a different address?
            </label>

            <div id="shipping-box" class="{{ old('ship_to_different_address') ? '' : 'hidden' }} space-y-4 border-t border-slate-100 pt-5">
                <h2 class="font-medium text-lg">Shipping details</h2>
                <div class="grid sm:grid-cols-2 gap-4">
                    <div class="sm:col-span-2">
                        <label class="block text-sm mb-1">Full Name</label>
                        <input name="shipping_name" value="{{ old('shipping_name') }}" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                        @error('shipping_name')<p class="text-rose-600 text-xs mt-1">{{ $message }}</p>@enderror
                    </div>

                    <div class="sm:col-span-2">
                        <label class="block text-sm mb-1">Street Address</label>
                        <input name="shipping_address_line1" placeholder="House number and street name"
                               value="{{ old('shipping_address_line1') }}"
                               class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm mb-2">
                        <input name="shipping_address_line2" placeholder="Apartment, suite, unit, etc. (optional)"
                               value="{{ old('shipping_address_line2') }}"
                               class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                        @error('shipping_address_line1')<p class="text-rose-600 text-xs mt-1">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label class="block text-sm mb-1">Country</label>
                        <select name="shipping_country" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                            @foreach(config('countries') as $code => $name)
                                <option value="{{ $code }}" @selected(old('shipping_country', 'IN') === $code)>{{ $name }}</option>
                            @endforeach
                        </select>
                        @error('shipping_country')<p class="text-rose-600 text-xs mt-1">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label class="block text-sm mb-1">City</label>
                        <input name="shipping_city" value="{{ old('shipping_city') }}" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                        @error('shipping_city')<p class="text-rose-600 text-xs mt-1">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label class="block text-sm mb-1">State</label>
                        <input name="shipping_state" value="{{ old('shipping_state') }}" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                        @error('shipping_state')<p class="text-rose-600 text-xs mt-1">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label class="block text-sm mb-1">Pin code</label>
                        <input name="shipping_postal_code" id="shipping_postal_code" maxlength="6" inputmode="numeric"
                               value="{{ old('shipping_postal_code') }}"
                               class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                        @error('shipping_postal_code')<p class="text-rose-600 text-xs mt-1">{{ $message }}</p>@enderror
                    </div>

                    
                </div>
            </div>

            <div>
                <h2 class="font-medium text-lg mb-2">Payment method</h2>
                <div class="space-y-2 text-sm">
                    @if($codEnabled)
                        <label class="flex gap-2 items-center"><input type="radio" name="payment_method" value="cod" checked> Cash on Delivery</label>
                    @endif
                    <!-- @if($onlineEnabled)
                        <label class="flex gap-2 items-center"><input type="radio" name="payment_method" value="paytm" @checked(! $codEnabled)> Paytm (Online)</label>
                    @endif -->
                    @if(! $codEnabled && ! $onlineEnabled)
                        <p class="text-rose-600">No payment methods are enabled.</p>
                    @endif
                </div>
            </div>

            <div>
                <label class="block text-sm mb-1">Order notes (optional)</label>
                <textarea name="notes" rows="3" placeholder="Notes about your order, e.g. special notes for delivery."
                          class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">{{ old('notes') }}</textarea>
            </div>

            <button type="submit" data-loading="Placing order..." class="rounded-lg bg-brand hover:bg-brand-dark text-white px-5 py-2.5 text-sm font-medium">
                Place order
            </button>
        </form>

        <div class="lg:col-span-2">
            <div class="rounded-xl border border-slate-200 bg-white p-5 sticky top-24">
                <h2 class="font-medium mb-4">Order summary</h2>
                <div class="space-y-3 text-sm mb-4">
                    @foreach($summary['items'] as $item)
                        <div class="flex justify-between gap-3">
                            <span class="text-slate-600">
                                {{ $item['name'] }} @if($item['variant_label']) ({{ $item['variant_label'] }}) @endif × {{ $item['quantity'] }}
                                @if($item['offer'])
                                    <span class="block mt-0.5 text-xs text-emerald-700">
                                        @if($item['offer']['name']){{ $item['offer']['name'] }} · @endif{{ $item['offer']['label'] }}
                                    </span>
                                @endif
                            </span>
                            <span class="text-right whitespace-nowrap">
                                @if($item['discount'] > 0)
                                    <span class="block text-xs text-slate-400 line-through">₹{{ number_format($item['line_subtotal'], 2) }}</span>
                                @endif
                                ₹{{ number_format($item['line_total'], 2) }}
                            </span>
                        </div>
                    @endforeach
                </div>
                <div class="space-y-2 text-sm border-t border-slate-100 pt-4">
                    @if($summary['discount'] > 0)
                        <div class="flex justify-between text-slate-500"><span>Items</span><span>₹{{ number_format($summary['gross_subtotal'], 2) }}</span></div>
                        <div class="flex justify-between text-emerald-700"><span>Offer savings</span><span id="sum-discount">−₹{{ number_format($summary['discount'], 2) }}</span></div>
                    @endif
                    <div class="flex justify-between"><span>Subtotal</span><span id="sum-subtotal">₹{{ number_format($summary['subtotal'], 2) }}</span></div>
                    <div class="flex justify-between"><span>Shipping</span><span id="sum-shipping">₹{{ number_format($summary['shipping'], 2) }}</span></div>
                    <div class="flex justify-between"><span>GST ({{ $summary['tax_percent'] }}%)</span><span id="sum-tax">₹{{ number_format($summary['tax'], 2) }}</span></div>
                    <div class="flex justify-between font-semibold text-base pt-2"><span>Total</span><span id="sum-total">₹{{ number_format($summary['total'], 2) }}</span></div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.getElementById('ship-different')?.addEventListener('change', function () {
    document.getElementById('shipping-box').classList.toggle('hidden', !this.checked);
});
</script>
@endpush
