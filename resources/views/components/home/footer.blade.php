<footer>
  <div class="container">
    <div class="footer-grid">
      <div class="footer-brand">
        <img src="{{ asset('assets/images/logo-bg.png') }}" alt="Elephant Spices" />
        <p>
          We are the leading manufacturer and supplier of spices because our
          products' quality derives from control of the entire production
          chain.
        </p>
      </div>
      <div>
        <h3>Quick Links</h3>
        <ul>
          <li><a href="{{ route('shop.home') }}">Home</a></li>
          <li><a href="{{ route('shop.about') }}">About Us</a></li>
          <li><a href="{{ route('shop.catalog') }}">Shop</a></li>
          <li><a href="{{ route('shop.contact') }}">Contact Us</a></li>
          <li><a href="{{ route('shop.shipping-policy') }}">Shipping Policy</a></li>
          <li><a href="{{ route('shop.privacy-policy') }}">Privacy Policy</a></li>
          <li><a href="{{ route('shop.refund-policy') }}">Refund &amp; Cancellation Policy</a></li>
          <li><a href="{{ route('shop.terms') }}">Terms &amp; Conditions</a></li>
        </ul>
      </div>
      <div>
        <h3>Categories</h3>
        <ul>
          <li><a href="{{ route('shop.catalog', ['category' => 'blended-masala']) }}">Blended Masala</a></li>
          <li><a href="{{ route('shop.catalog', ['category' => 'pure-powder-spices']) }}">Pure Powder Spices</a></li>
          <li><a href="{{ route('shop.catalog', ['category' => 'vrat-atta']) }}">Vrat Atta</a></li>
          <li><a href="{{ route('shop.catalog', ['category' => 'salt']) }}">Salt</a></li>
        </ul>
      </div>
      <div>
        <h3>Contact Us</h3>
        <ul>
          <li><a href="tel:9915954385">9915954385</a></li>
          <li><a href="mailto:info@elephantspices.com">info@elephantspices.com</a></li>
          <li>Amritsar Haldi Sales Corporation,
            F 22/44, Public Sahara Gali, Opposite Pillar No. 67 ,
            Batala Road,
            Amritsar - 143001 (Punjab).</li>
        </ul>
      </div>
    </div>
    <div class="footer-base">
      <span>© 2026 Elephant Spices. All rights reserved.</span><span class="socials"><a href="#"><i
            class="fa-brands fa-facebook-f"></i></a><a href="#"><i class="fa-brands fa-instagram"></i></a><a href="#"><i
            class="fa-brands fa-x-twitter"></i></a></span>
    </div>
  </div>
</footer>

<a href="https://wa.me/919915954385" class="whatsapp-float" target="_blank" rel="noopener noreferrer"
  aria-label="Chat with us on WhatsApp">
  <span class="whatsapp-icon"><i class="fa-brands fa-whatsapp"></i></span>
</a>