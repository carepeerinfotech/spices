<!doctype html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="csrf-token" content="{{ csrf_token() }}" />
    <title>Elephant Spices — Authentic Indian Flavours</title>
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://images.unsplash.com" />
    <link
      href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Playfair+Display:wght@600;700;800&display=swap"
      rel="stylesheet"
    />
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:ital,wght@0,100..900;1,100..900&display=swap" rel="stylesheet">
    <link
      rel="stylesheet"
      href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.3.0/css/all.min.css"
      crossorigin="anonymous"
      referrerpolicy="no-referrer"
    />
     <link rel="icon" type="image/x-icon" href="assets/images/elephant-fav.png">
    <link rel="stylesheet" href="{{ asset('css/home.css') }}" />
  </head>
  <body>
    <x-home.header :cart-count="$cartCount" />
    <main>
      <x-home.hero />
      <x-home.featured-collections :categories="$categories" />
      <x-home.best-sellers :products="$featuredProducts" />
      <x-home.why-us />
      <x-home.story />
      <x-home.process />
      <x-home.reviews />
      <x-home.newsletter />
    </main>
    <x-home.footer />
    <script src="{{ asset('js/app-ajax.js') }}"></script>
    <script src="{{ asset('js/home.js') }}"></script>
    <script src="{{ asset('js/search.js') }}"></script>
    <script src="{{ asset('js/cart-drawer.js') }}"></script>
    <script src="{{ asset('js/product-variants.js') }}"></script>
  </body>
</html>
