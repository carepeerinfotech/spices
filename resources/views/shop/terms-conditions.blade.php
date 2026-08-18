@extends('shop.layouts.app')

@section('title', 'Terms & Conditions — '.config('app.name'))
@section('meta_description', 'The terms and conditions governing your use of Elephant Spices and its products.')

@section('content')
<x-shop.breadcrumb title="Terms & Conditions" />

<article class="max-w-3xl mx-auto px-4 sm:px-6 py-12 sm:py-16">
    <div class="prose prose-slate max-w-none text-stone-700 leading-relaxed space-y-6">

        <section>
            <h2 class="font-display text-xl text-stone-900 mb-2">1. Introduction</h2>
            <p>These terms and conditions govern your use of this website (<strong>"Elephant Spices"</strong> or the <strong>"website"</strong>). By continuing to use the website, you agree that you have read, understood, and are bound by the terms and conditions set out here. If you disagree with these terms and conditions, or any part of them, you must not continue to use this website. For the purpose of these terms and conditions, "we", "our" and "us" refer to Elephant Spices, and "you", "your", "customer" and "viewer" refer to the user or consumer of the website.</p>
        </section>

        <section>
            <h2 class="font-display text-xl text-stone-900 mb-2">2. Services Overview</h2>
            <p>Elephant Spices is an online store offering pure powder spices, blended masalas, vrat atta, salt and related food products for sale and delivery, marketed by us and/or our business partners.</p>
        </section>

        <section>
            <h2 class="font-display text-xl text-stone-900 mb-2">3. Eligibility Criteria</h2>
            <p>Use of this website is limited to persons who are above 18 years of age and capable of forming a contract under the provisions of the Indian Contract Act, 1872. By continuing to use this website, you represent that you are not a minor. If you are a minor and wish to use the website, you may only do so under the supervision of a parent or legal guardian who accepts and agrees to be bound by these terms and conditions on your behalf.</p>
        </section>

        <section>
            <h2 class="font-display text-xl text-stone-900 mb-2">4. Account, Password and Security</h2>
            <p>You may be required to register and log in to place orders on the website. You are responsible for maintaining the confidentiality of your account password and for all activity that occurs under your account. You agree to notify us immediately of any unauthorised use of your account or password. We shall not be held liable for any loss or damage arising from your failure to comply with this provision.</p>
        </section>

        <section>
            <h2 class="font-display text-xl text-stone-900 mb-2">5. Pricing and Terms of Payment</h2>
            <p class="mb-3">All products listed on the website are sold at the prices shown on the website, in Indian Rupees. Unless otherwise stated, prices are exclusive of shipping/delivery charges and GST, where applicable; these will be added to the order total before checkout.</p>
            <p class="mb-3">We reserve the right to notify you of any errors in product descriptions or pricing prior to dispatch. If you choose to proceed with such an order, it will be fulfilled in accordance with the corrected description and/or price. Prices do not generally change on a daily basis, but where a change is required we will endeavour to update the website promptly, without prior notice. Any price change that takes effect after your order has been placed will not affect the price you have already agreed to pay, except where the change is due to a revision in applicable tax.</p>
            <p>We reserve the right to revise prices, or the manner or timelines of delivery, at our discretion — including where costs of materials, labour, delivery, or taxes change. Payment must be made through the method indicated on the website and, once made, is unconditional and irrevocable.</p>
        </section>

        <section>
            <h2 class="font-display text-xl text-stone-900 mb-2">6. Customer's Representation</h2>
            <p class="mb-3">You represent that all information provided during registration or while placing an order is accurate, up to date, and sufficient for us to fulfil your order. Any warranties on our products extend only to you as a customer, and not to any reseller of our products.</p>
            <p>Some customers may be allergic to, or have medical conditions requiring them to avoid or restrict, certain ingredients. You are expected to review the ingredient list and product information before purchasing or consuming any product. We shall not be held responsible for any reaction, allergy, or health-related issue arising from your use or consumption of any product listed on this website.</p>
        </section>

        <section>
            <h2 class="font-display text-xl text-stone-900 mb-2">7. Unauthorized or Fraudulent Use</h2>
            <p>As set out in Clause 3, use of this website is limited to persons 18 years of age or above (or minors acting under the supervision of a parent/guardian, as described in that clause). Use of the website in breach of this condition shall be deemed unauthorised use. From time to time, we may extend promotional offers to existing or potential customers; continued use of such offers constitutes agreement to their specific terms. Non-adherence to those terms while purchasing products or accessing the website shall be treated as fraudulent use, and you shall be responsible for any resulting damages.</p>
        </section>

        <section>
            <h2 class="font-display text-xl text-stone-900 mb-2">8. User Conduct and Rules</h2>
            <ul class="list-[lower-alpha] pl-5 space-y-1.5">
                <li>You must not modify, copy, distribute, transmit, display, perform, reproduce, publish, license, create derivative works from, sell, lease, translate, decompile, or otherwise exploit the website or any part of it, except as expressly permitted.</li>
                <li>You must not post or publish content on the website that is abusive, defamatory, threatening, obscene, indecent, disparaging, pornographic, harassing, or that violates the legal rights of others or would otherwise give rise to civil or criminal liability under Indian law.</li>
                <li>You must not upload or distribute any virus, bug, trap door, harmful code, or similar software that could damage the functioning of the website or another person's computer.</li>
                <li>You must not violate, abuse, manipulate, or exploit these terms and conditions or any other policy or guideline on the website, and must not post any defamatory, derogatory, abusive, or disparaging statement about us, our website, or our associates and partners.</li>
                <li>You must not attempt to obtain any information or material through means not intentionally made available through this website. We shall not be liable for any loss or damage arising from us being prevented, hindered, or delayed in performance by circumstances beyond our reasonable control — including acts of God, war, riot, civil commotion, government action, explosion, fire, flood, storm, epidemic, pandemic, lockdown, accident, strike, or breakdown of plant, machinery, power, or materials supply. In such an event, we may cancel your order and refund any payment made, or delay delivery, as appropriate.</li>
            </ul>
        </section>

        <section>
            <h2 class="font-display text-xl text-stone-900 mb-2">9. Links to Third-Party Sites</h2>
            <p>This website may contain links to websites operated by other companies. Your use of any such third-party website is not governed by these terms and conditions. These sites are not controlled by us, and we are not responsible for their content. Such links are provided purely for your convenience, and their inclusion does not imply any endorsement by us.</p>
        </section>

        <section>
            <h2 class="font-display text-xl text-stone-900 mb-2">10. Disclaimer</h2>
            <p>We make reasonable efforts to ensure the information provided on this website is accurate, but we make no representation as to the suitability, reliability, availability, accuracy, or completeness of any data, information, product, or service on it. We do not warrant that the website will be uninterrupted, error-free, or free of viruses or other harmful components. We shall not be liable for any punitive or consequential damages arising from your use of, or inability to use, the website.</p>
        </section>

        <section>
            <h2 class="font-display text-xl text-stone-900 mb-2">11. Objectionable Material</h2>
            <p>You may occasionally encounter content that you find offensive, indecent, or objectionable. You agree to use the website and its content at your own risk, and we shall not be held liable for any content that appears objectionable or indecent to you.</p>
        </section>

        <section>
            <h2 class="font-display text-xl text-stone-900 mb-2">12. Reviews, Feedback and Comments</h2>
            <p>You may submit reviews, feedback, comments, or suggestions on the website. Such submissions are not confidential and become our property. By submitting a review or comment, you grant us the right to use, copy, distribute, display, modify, and create derivative works from it, along with any name submitted with it, without restriction. You agree that your submissions will not violate our policies or any other person's rights, and will not contain illegal, scandalous, offensive, obscene, disparaging, or threatening material.</p>
        </section>

        <section>
            <h2 class="font-display text-xl text-stone-900 mb-2">13. Termination</h2>
            <p>We reserve the right to suspend or terminate your use of the website without prior notice if we find that you have violated any of these terms and conditions, or any related policy or guideline.</p>
        </section>

        <section>
            <h2 class="font-display text-xl text-stone-900 mb-2">14. Modification of Terms and Conditions</h2>
            <p>We reserve the right to revise these terms and conditions at any time, at our discretion and without prior notice. Any revision will take effect from the date it is published on the website, so please review this page periodically. If you do not accept the modified terms, you should discontinue using the website; continued use after a revision constitutes your acceptance of the updated terms.</p>
        </section>

        <section>
            <h2 class="font-display text-xl text-stone-900 mb-2">15. Indemnification</h2>
            <p class="mb-2">You agree to indemnify, defend, and hold us harmless against any losses, costs, expenses, suits, or proceedings (including reasonable attorney fees), and any third-party claims, arising out of:</p>
            <ul class="list-[lower-roman] pl-5 space-y-1.5">
                <li>your use or unauthorised use of the website;</li>
                <li>your violation of these terms and conditions or any of our policies;</li>
                <li>your violation of any applicable law, rule, or regulation;</li>
                <li>your infringement of the intellectual property rights of a third party; or</li>
                <li>your submission or uploading of any illegal, scandalous, offensive, obscene, disparaging, or threatening material on the website.</li>
            </ul>
        </section>

        <section>
            <h2 class="font-display text-xl text-stone-900 mb-2">16. Severability</h2>
            <p>If any provision of these terms and conditions is found by a court or other competent authority to be unlawful or unenforceable, the remaining provisions will continue to have full effect. Where an unlawful or unenforceable provision would be valid if part of it were deleted, that part will be deemed deleted, and the rest of the provision will remain in effect.</p>
        </section>

        <section>
            <h2 class="font-display text-xl text-stone-900 mb-2">17. Governing Law</h2>
            <p>These terms and conditions are governed by the laws of India, and the competent courts of India shall have exclusive jurisdiction over any dispute arising out of or in connection with them.</p>
        </section>

        <section>
            <h2 class="font-display text-xl text-stone-900 mb-2">18. Contact Us</h2>
            <p>If you have any queries, grievances, feedback, or suggestions, please contact us at <a href="mailto:info@elephantspices.com" class="text-brand">info@elephantspices.com</a> or <a href="tel:9915954385" class="text-brand">9915954385</a>.</p>
        </section>

        <section>
            <h2 class="font-display text-xl text-stone-900 mb-2">19. Infringement Policy</h2>
            <p class="mb-3">Using or accessing this website does not grant you any right, title, or interest in its content or any materials published or displayed on it.</p>
            <p class="mb-3">All content on this website — including logos, text, graphics, software, service names, trade names, the user interface, source code, icons, and images — is the exclusive property of Elephant Spices, and all intellectual property rights in that content are reserved. You agree not to use, reproduce, sell, transmit, create derivative works from, or distribute any content from this website. You may use protected content solely for personal use, and any other use requires our prior written permission. Access to the website does not grant you any licence, express or implied, to our intellectual property or that of our licensors.</p>
            <p class="mb-2">We respect the intellectual property rights of others. If you believe any content displayed on this website infringes a copyright, patent, design, or trademark, please notify us with the following details:</p>
            <ul class="list-decimal pl-5 space-y-1.5 mb-3">
                <li>Your contact information, including name, address, telephone number, and email address.</li>
                <li>A digital or electronic signature of the person authorised to act on behalf of the rights owner, along with a statement confirming that the person is the authorised representative of that owner.</li>
                <li>A description of the allegedly infringing material and its location on the website, along with an explanation of how it infringes the relevant right.</li>
                <li>A statement that you believe, in good faith, that the material infringes the rights of another person.</li>
            </ul>
            <p>This information is necessary for us to verify the claim and investigate the matter. Where the claim is found to have merit, we will remove the alleged infringing material and disable access to it.</p>
        </section>

    </div>
</article>
@endsection
