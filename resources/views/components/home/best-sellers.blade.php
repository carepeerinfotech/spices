@props(['products'])
@php
    $fallbackImages = ['mirch-powder.png', 'turmenic-powder.png', 'jeera-sabut.png', 'dhania-powder.png'];
@endphp
<section class="section" id="shop">
  <div class="container">
    <h2 class="section-title">Best Sellers</h2>
    <div class="products" style="margin-top: 34px">
      @foreach($products as $product)
        <article class="product">
          <div class="product-img">
            <img
              src="{{ $product->primaryImageUrl() ?: asset('assets/images/'.$fallbackImages[$loop->index % count($fallbackImages)]) }}"
              alt="{{ $product->name }}"
            />
          </div>
          <h3>{{ $product->name }}</h3>
          <p class="rating">★★★★★ (4.8 · 2,340 reviews)</p>
          <p class="price">
            ₹{{ number_format($product->minPrice(), 0) }}
            @if($product->compare_price && (float) $product->compare_price > $product->minPrice())
              <del>₹{{ number_format($product->compare_price, 0) }}</del>
            @endif
          </p>
          <button class="btn">Add to Cart</button>
        </article>
      @endforeach
    </div>
  </div>
</section>
