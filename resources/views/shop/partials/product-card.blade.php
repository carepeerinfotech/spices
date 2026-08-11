<div class="product-card group">
    <a href="{{ route('shop.product', $product->slug) }}" class="product-card__media block">
        @if($product->primaryImageUrl())
            <img src="{{ $product->primaryImageUrl() }}" alt="{{ $product->name }}" loading="lazy">
        @endif
    </a>
    <div class="product-card__body">
        <a href="{{ route('shop.product', $product->slug) }}">
            <h3 class="product-card__title">{{ $product->name }}</h3>
            <p class="product-card__meta">{{ $product->short_description ?: 'Authentic, Fresh and Pure' }}</p>
            @if($product->hasRunningOffer())
                <p class="product-card__price">
                    {{ $product->formattedOfferPrice() }}
                    <span class="text-stone-400 line-through text-sm font-normal ml-1">{{ $product->formattedPrice() }}</span>
                </p>
                <span class="inline-flex items-center rounded-full bg-emerald-50 text-emerald-800 border border-emerald-200 px-2 py-0.5 text-xs font-medium">
                    {{ $product->activeOffer()->label() }}
                </span>
            @else
                <p class="product-card__price">{{ $product->formattedPrice() }}</p>
            @endif
        </a>
        <div class="product-card__cta">
            <form data-ajax method="POST" action="{{ route('shop.cart.store') }}"
                  onsubmit="this.addEventListener('ajax:done', function (e) { if (e.detail.ok && e.detail.data.data && window.AppAjax) { AppAjax.updateCartBadge(e.detail.data.data.item_count || e.detail.data.data.count); } }, { once: true });">
                @csrf
                <input type="hidden" name="product_id" value="{{ $product->id }}">
                @if($product->defaultVariant())
                    <input type="hidden" name="variant_id" value="{{ $product->defaultVariant()->id }}">
                @endif
                <input type="hidden" name="quantity" value="1">
                <button type="submit" class="btn-brand">Add to Cart</button>
            </form>
        </div>
    </div>
</div>
