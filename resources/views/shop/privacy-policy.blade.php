@extends('shop.layouts.app')

@section('title', 'Privacy Policy — '.config('app.name'))
@section('meta_description', 'How Elephant Spices collects, uses, discloses and protects your personal information.')

@section('content')
<x-shop.breadcrumb title="Privacy Policy" />

<article class="max-w-3xl mx-auto px-4 sm:px-6 py-12 sm:py-16">
    <div class="prose prose-slate max-w-none text-stone-700 leading-relaxed space-y-6">

        <p>At <strong>Elephant Spices</strong>, we respect your privacy and are committed to protecting it. This Privacy Policy explains how we collect, use, disclose and safeguard your personal data when you use this website, in line with the Digital Personal Data Protection Act, 2023 and other applicable data protection laws. We may update this policy from time to time, so please check back periodically.</p>

        <section>
            <h2 class="font-display text-xl text-stone-900 mb-2">Information We Collect</h2>
            <p class="mb-2"><strong>Information you give us</strong> — such as your name, email address, phone number, shipping/billing address, and payment details, when you register, place an order, or contact support.</p>
            <p><strong>Information collected automatically</strong> — such as your IP address, browser and device details, pages viewed, and browsing patterns, using cookies and similar technologies, so we can keep you signed in, remember your cart, and understand how visitors use our site.</p>
        </section>

        <section>
            <h2 class="font-display text-xl text-stone-900 mb-2">How We Use Your Information</h2>
            <p>We use your information to process and deliver your orders, take payment, send order and account updates, respond to support requests, run promotions you've opted into, and improve our website and product offering. We do not sell your personal data to third parties.</p>
        </section>

        <section>
            <h2 class="font-display text-xl text-stone-900 mb-2">Payment Information</h2>
            <p>Payments are processed securely through our payment gateway partners, whose own privacy policies govern their handling of your payment details. We do not store your card, UPI, or net-banking credentials on our servers.</p>
        </section>

        <section>
            <h2 class="font-display text-xl text-stone-900 mb-2">Disclosure of Your Information</h2>
            <p>We may share your information with trusted service providers who help us operate our business — such as courier and delivery partners, payment processors, and email/SMS providers — solely to fulfil your order and provide our services. We may also disclose information where required by law, to enforce our terms, or to protect the rights, property, or safety of Elephant Spices and our customers.</p>
        </section>

        <section>
            <h2 class="font-display text-xl text-stone-900 mb-2">Data Retention</h2>
            <p>We retain your personal data for as long as necessary to fulfil the purposes described in this policy, and as required to meet our legal, accounting, or reporting obligations.</p>
        </section>

        <section>
            <h2 class="font-display text-xl text-stone-900 mb-2">Your Rights</h2>
            <p>You may ask us to provide a copy of the personal data we hold about you, correct inaccurate details, or delete your data, subject to applicable law. You can also nominate another individual to exercise these rights on your behalf in the event of your death or incapacity. To exercise any of these rights, contact us using the details below — we aim to resolve requests within 30 days.</p>
        </section>

        <section>
            <h2 class="font-display text-xl text-stone-900 mb-2">Children's Privacy</h2>
            <p>This website is not intended for individuals under 18 years of age, and we do not knowingly collect personal data from children. If you believe your child has provided us with personal data, please contact us to request its deletion.</p>
        </section>

        <section>
            <h2 class="font-display text-xl text-stone-900 mb-2">Data Security</h2>
            <p>We use commercially reasonable, industry-standard safeguards to protect your personal data against unauthorised access, alteration, disclosure, or destruction. Please also do your part — use a strong, unique password and keep your login details private.</p>
        </section>

        <section>
            <h2 class="font-display text-xl text-stone-900 mb-2">Contact Us</h2>
            <p>Questions, requests, or complaints about this policy? Reach out at <a href="mailto:info@elephantspices.com" class="text-brand">info@elephantspices.com</a> or <a href="tel:9876543210" class="text-brand">9876543210</a>.</p>
        </section>

    </div>
</article>
@endsection
