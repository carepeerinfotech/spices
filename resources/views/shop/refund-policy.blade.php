@extends('shop.layouts.app')

@section('title', 'Refund & Cancellation Policy — '.config('app.name'))
@section('meta_description', 'Our order cancellation, returns, damaged/incorrect product, and refund process for Elephant Spices orders.')

@section('content')
<x-shop.breadcrumb title="Refund & Cancellation Policy" />

<article class="max-w-3xl mx-auto px-4 sm:px-6 py-12 sm:py-16">
    <div class="prose prose-slate max-w-none text-stone-700 leading-relaxed space-y-6">
        <p>At <strong>Elephant Spices</strong>, we are committed to delivering premium-quality spices and ensuring a smooth shopping experience for every customer. Please read our Refund &amp; Cancellation Policy carefully before placing your order.</p>

        <section>
            <h2 class="font-display text-xl text-stone-900 mb-2">Order Cancellation</h2>
            <ul class="list-disc pl-5 space-y-1.5">
                <li>Orders may be cancelled <strong>only before they have been shipped</strong>.</li>
                <li>If your cancellation request is approved before dispatch, the refund will be processed to your original payment method within <strong>5–10 working days</strong>.</li>
                <li>Once an order has been dispatched from our warehouse, it <strong>cannot be cancelled</strong>.</li>
                <li>If a customer refuses to accept a dispatched order at the time of delivery, a handling and logistics charge of <strong>₹199</strong> will be deducted from the refund.</li>
            </ul>
        </section>

        <section>
            <h2 class="font-display text-xl text-stone-900 mb-2">Returns</h2>
            <p class="mb-3">Elephant Spices does not offer a general return policy for products once they have been delivered.</p>
            <p class="mb-2">If the delivery cannot be completed due to:</p>
            <ul class="list-disc pl-5 space-y-1.5 mb-3">
                <li>Customer unavailability at the delivery address,</li>
                <li>Incorrect delivery details provided by the customer, or</li>
                <li>Failure to verify the delivery OTP (where applicable),</li>
            </ul>
            <p>the order will be returned to us. After the product is received back in good condition, a refund will be processed after deducting <strong>₹199 or the order value (whichever is higher)</strong> towards shipping and handling charges.</p>
        </section>

        <section>
            <h2 class="font-display text-xl text-stone-900 mb-2">Damaged, Defective or Incorrect Products</h2>
            <p class="mb-2">If you receive:</p>
            <ul class="list-disc pl-5 space-y-1.5 mb-3">
                <li>A damaged product,</li>
                <li>An expired product,</li>
                <li>A product with defective packaging,</li>
                <li>A leaking package, or</li>
                <li>An incorrect item,</li>
            </ul>
            <p class="mb-3">please contact our customer support within <strong>24 hours</strong> of delivery.</p>
            <p class="mb-2">To help us investigate your concern, kindly provide:</p>
            <ul class="list-disc pl-5 space-y-1.5 mb-3">
                <li>Your order number</li>
                <li>Product name(s)</li>
                <li>Clear photographs of the product and packaging</li>
                <li>A brief description of the issue</li>
                <li>For leakage, damaged packaging, or missing items, an <strong>unboxing video</strong> is mandatory</li>
            </ul>
            <p>Claims submitted after <strong>24 hours</strong> from the time of delivery may not be accepted.</p>
        </section>

        <section>
            <h2 class="font-display text-xl text-stone-900 mb-2">Quality Review Process</h2>
            <p class="mb-3">Once we receive your complaint, our customer support team will review the details. If required, we may request that the product be returned for inspection. In such cases:</p>
            <ul class="list-disc pl-5 space-y-1.5 mb-3">
                <li>We will provide return shipping instructions.</li>
                <li>Products returned without prior approval or without following our instructions may not be accepted.</li>
                <li>After receiving the returned product, our quality team will conduct a detailed inspection.</li>
            </ul>
            <p>If the issue is confirmed to be genuine, we will provide an appropriate resolution, which may include a <strong>replacement product, store credit, or a coupon of equivalent value</strong>, at the sole discretion of Elephant Spices. <strong>Cash refunds will not be issued</strong> for approved quality-related claims after delivery.</p>
        </section>

        <section>
            <h2 class="font-display text-xl text-stone-900 mb-2">Order Marked as Delivered but Not Received</h2>
            <p class="mb-3">If your order is marked as delivered but you have not received it, please contact our customer support within <strong>24 hours</strong> of receiving the delivery notification via email or SMS.</p>
            <p>Our team will investigate the matter with the delivery partner and, if the claim is found to be genuine, we will resolve the issue as quickly as possible.</p>
        </section>

        <section>
            <h2 class="font-display text-xl text-stone-900 mb-2">Contact Us</h2>
            <p class="mb-2">If you have any questions regarding cancellations, refunds, or product quality, please reach out to our customer support team.</p>
            <p><strong>Email:</strong> <a href="mailto:info@elephantspices.com" class="text-brand">info@elephantspices.com</a></p>
            <p><strong>Phone:</strong> <a href="tel:9876543210" class="text-brand">9876543210</a></p>
        </section>
    </div>
</article>
@endsection
