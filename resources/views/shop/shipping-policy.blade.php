@extends('shop.layouts.app')

@section('title', 'Shipping Policy — '.config('app.name'))
@section('meta_description', 'How Elephant Spices processes, ships and delivers your order across India.')

@section('content')
<x-shop.breadcrumb title="Shipping Policy" />

<article class="max-w-3xl mx-auto px-4 sm:px-6 py-12 sm:py-16">
    <div class="prose prose-slate max-w-none text-stone-700 leading-relaxed space-y-6">
        <section>
            <h2 class="font-display text-xl text-stone-900 mb-2">Delivery Partners</h2>
            <p>Delivery is carried out through our fulfillment partners, who maintain a dedicated team of delivery personnel and a fleet of vehicles operating across the country — ensuring timely and accurate delivery to our customers.</p>
        </section>
        <section>
            <h2 class="font-display text-xl text-stone-900 mb-2">Order Dispatch</h2>
            <p>It is our endeavour to deliver the freshest spices to your doorstep. Hence, we ship your order within 72 hours of order confirmation.</p>
        </section>
        <section>
            <h2 class="font-display text-xl text-stone-900 mb-2">Delivery Timelines</h2>
            <p>Final delivery typically takes around 5–7 working days, depending on demand and courier availability in your area.</p>
        </section>
        <section>
            <h2 class="font-display text-xl text-stone-900 mb-2">Shipping Charges</h2>
            <p>To ensure your order is delivered safely and securely, we work with some of the best courier services in the country. A shipping fee is charged to cover this service, and the exact amount is added to your cart value before you make payment.</p>
        </section>
        <section>
            <h2 class="font-display text-xl text-stone-900 mb-2">Tracking Your Order</h2>
            <p>You'll be notified of your tracking details by email. You can also track your order anytime from the <a href="{{ route('account.dashboard') }}" class="text-brand">My Account</a> section.</p>
        </section>
        <section>
            <h2 class="font-display text-xl text-stone-900 mb-2">Undeliverable Pincodes</h2>
            <p>If your pincode isn't serviceable, we'll notify you before shipping and offer a full refund for that order.</p>
        </section>
    </div>
</article>
@endsection
