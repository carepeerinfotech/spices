@props(['products'])
@php
    $fallbackImages = ['mirch-powder.png', 'turmenic-powder.png', 'jeera-sabut.png', 'dhania-powder.png'];
@endphp
<section class="section" id="shop">
  <div class="container">
    <h2 class="section-title heading-center">Best Sellers</h2>
    <div class="products" style="margin-top: 34px">
      @foreach($products as $product)
        <article class="product">
          <div class="product-img">
            <img
              src="{{ $product->primaryImageUrl() ?: asset('assets/images/'.$fallbackImages[$loop->index % count($fallbackImages)]) }}"
              alt="{{ $product->name }}"
            />
          </div>
          <div class="product-content">
          <h3>{{ $product->name }}</h3>
          <!-- <p class="rating">★★★★★ (4.8 · 2,340 reviews)</p> -->
          <p class="price" data-price>
            ₹{{ number_format($product->defaultVariant()?->price ?? $product->minPrice(), 0) }}
            @if($product->compare_price && (float) $product->compare_price > $product->minPrice())
              <del>₹{{ number_format($product->compare_price, 0) }}</del>
            @endif
          </p>
          @if($product->variants->count() > 1)
            <div class="variant-chips" role="group" aria-label="Choose a size">
              @foreach($product->variants as $variant)
                <button type="button"
                        class="variant-chip {{ $product->defaultVariant()?->id === $variant->id ? 'active' : '' }}"
                        data-variant-chip
                        data-id="{{ $variant->id }}"
                        data-price="₹{{ number_format($variant->price, 0) }}"
                        @disabled(!$variant->inStock())>
                  {{ $variant->option_label ?: $variant->sku }}
                </button>
              @endforeach
            </div>
          @endif
          <form data-ajax method="POST" action="{{ route('shop.cart.store') }}">
            @csrf
            <input type="hidden" name="product_id" value="{{ $product->id }}">
            @if($product->defaultVariant())
              <input type="hidden" name="variant_id" value="{{ $product->defaultVariant()->id }}">
            @endif
            <input type="hidden" name="quantity" value="1">
            <button type="submit" class="btn">Add to Cart</button>
          </form>
          </div>
        </article>
      @endforeach
    </div>
  </div>
</section>
