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
    <a href="{{ route('shop.home') }}">Home</a><a href="{{ route('shop.about') }}">About</a
    ><a href="{{ route('shop.catalog') }}">Shop</a><a href="{{ route('shop.contact') }}">Contact</a>
  </nav>
  <div class="icons">
    <a class="icon-btn" href="{{ route('shop.cart') }}" aria-label="Cart">
      <svg viewBox="0 0 24 24">
        <path d="M3 4h2l2 12h11l2-8H6" />
        <circle cx="9" cy="20" r="1" />
        <circle cx="18" cy="20" r="1" />
      </svg>
      <span class="cart-badge" data-cart-count @if($cartCount < 1) hidden @endif>{{ $cartCount }}</span></a
    ><a class="icon-btn" href="{{ route('shop.catalog') }}" aria-label="Search products">
      <svg viewBox="0 0 24 24">
        <circle cx="11" cy="11" r="6" />
        <path d="m16 16 5 5" />
      </svg></a
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
