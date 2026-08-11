@props(['categories'])
@php
    $fallbackImages = ['garam-masala1.png', 'mirch-powder.png', 'vrat-atta.png', 'rock-salt1.png'];
@endphp
<section class="section feature-collection">
  <div class="container">
    <h2 class="section-title heading-center">Featured Collections</h2>
    <div class="collections" style="margin-top: 32px">
      @foreach($categories as $category)
        <article class="collection">
           <a href="{{ route('shop.catalog', ['category' => $category->slug]) }}">
          <img
            src="{{ $category->imageUrl() ?: asset('assets/images/'.$fallbackImages[$loop->index % count($fallbackImages)]) }}"
            alt="{{ $category->name }}"
          />
          <div class="collection-data">
            <h3>{{ $category->name }}</h3>
            <a href="{{ route('shop.catalog', ['category' => $category->slug]) }}">Shop Now →</a>
          </div>
          </a>
        </article>
      @endforeach
    </div>
  </div>
</section>
