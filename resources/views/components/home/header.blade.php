@props(['cartCount' => 0])

<header>
  <a class="brand" href="{{ route('shop.home') }}"
    ><img src="{{ asset('assets/images/logo.png') }}" alt="Elephant Spices"
  /></a>
  <nav class="nav" id="site-nav">
    <div class="nav-drawer-head">
      <a href="{{ route('shop.home') }}"><img src="{{ asset('assets/images/logo.png') }}" alt="Elephant Spices"></a>
      <button type="button" class="nav-drawer-close" aria-label="Close menu">
        <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 6l12 12M18 6 6 18"/></svg>
      </button>
    </div>
    <a href="{{ route('shop.home') }}">Home</a><a href="{{ route('shop.about') }}">About Us</a
    ><a href="{{ route('shop.catalog') }}">Shop</a><a href="{{ route('shop.contact') }}">Contact Us</a>
  </nav>
  <div class="icons">
    <a class="icon-btn" href="{{ route('shop.cart') }}" aria-label="Cart">
      <svg viewBox="0 0 24 24">
        <path d="M3 4h2l2 12h11l2-8H6" />
        <circle cx="9" cy="20" r="1" />
        <circle cx="18" cy="20" r="1" />
      </svg>
      <span class="cart-badge" data-cart-count @if($cartCount < 1) hidden @endif>{{ $cartCount }}</span></a
    ><button type="button" class="icon-btn" data-search-toggle aria-label="Search products" aria-haspopup="dialog" aria-expanded="false" aria-controls="search-modal">
      <svg viewBox="0 0 24 24">
        <circle cx="11" cy="11" r="6" />
        <path d="m16 16 5 5" />
      </svg></button
    ><a class="icon-btn" href="{{ auth()->check() ? route('account.dashboard') : route('login') }}" aria-label="{{ auth()->check() ? 'My account' : 'Login' }}">
      <svg viewBox="0 0 24 24">
        <circle cx="12" cy="8" r="4" />
        <path d="M4 20c0-4.4 3.6-7 8-7s8 2.6 8 7" />
      </svg></a
    ><button
      class="icon-btn menu-toggle"
      aria-label="Menu"
      aria-expanded="false"
    >
      <svg viewBox="0 0 24 24"><path d="M4 7h16M4 12h16M4 17h16" /></svg>
    </button>
  </div>
</header>
<div class="nav-backdrop" aria-hidden="true"></div>

<div id="search-modal" class="search-modal" role="dialog" aria-modal="true" aria-label="Search products" hidden>
  <div class="search-modal__backdrop" data-search-close></div>
  <div class="search-modal__panel">
    <form class="search-modal__form" action="{{ route('shop.catalog') }}" method="GET">
      <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
      <input type="search" name="q" data-search-input placeholder="Search for spices, masala, atta..." autocomplete="off">
      <button type="button" class="search-modal__close" data-search-close aria-label="Close search">
        <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 6l12 12M18 6 6 18"/></svg>
      </button>
    </form>
    <div class="search-modal__results" data-search-results></div>
  </div>
</div>

<div id="cart-drawer" class="cart-drawer" role="dialog" aria-modal="true" aria-label="Your cart">
  <div class="cart-drawer__backdrop" data-cart-drawer-close></div>
  <div class="cart-drawer__panel">
    <div class="cart-drawer__header">
      <h2>Your Cart</h2>
      <button type="button" class="cart-drawer__close" data-cart-drawer-close aria-label="Close cart">
        <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 6l12 12M18 6 6 18"/></svg>
      </button>
    </div>
    <div class="cart-drawer__items" data-cart-drawer-items>
      <p class="cart-drawer__empty">Your cart is empty.</p>
    </div>
    <div class="cart-drawer__footer">
      <div class="cart-drawer__subtotal"><span>Subtotal</span><span data-cart-drawer-subtotal>₹0.00</span></div>
      <div class="cart-drawer__actions">
        <a href="{{ route('shop.cart') }}" class="cart-drawer__view">View Cart</a>
        <a href="{{ route('shop.checkout') }}" class="cart-drawer__checkout">Checkout</a>
      </div>
    </div>
  </div>
</div>
