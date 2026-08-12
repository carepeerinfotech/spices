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
                <p class="product-card__price" data-price>
                    {{ $product->formattedOfferPrice() }}
                    <span class="text-stone-400 line-through text-sm font-normal ml-1">{{ $product->formattedPrice() }}</span>
                </p>
                <span class="inline-flex items-center rounded-full bg-emerald-50 text-emerald-800 border border-emerald-200 px-2 py-0.5 text-xs font-medium">
                    {{ $product->activeOffer()->label() }}
                </span>
            @else
                <p class="product-card__price" data-price>{{ $product->defaultVariant()?->formattedPrice() ?: $product->formattedPrice() }}</p>
            @endif
        </a>
        @if($product->variants->count() > 1)
            <div class="flex flex-wrap gap-1.5 my-2" role="group" aria-label="Choose a size">
                @foreach($product->variants as $variant)
                    <button type="button"
                            class="variant-chip px-2 py-1 rounded-full border text-[11px] font-medium transition-colors {{ $product->defaultVariant()?->id === $variant->id ? 'is-active border-brand bg-cream text-brand' : 'border-[var(--line)] text-stone-600 hover:border-brand' }} disabled:opacity-40 disabled:cursor-not-allowed"
                            data-variant-chip
                            data-id="{{ $variant->id }}"
                            data-price="{{ $variant->formattedPrice() }}"
                            @disabled(!$variant->inStock())>
                        {{ $variant->option_label ?: $variant->sku }}
                    </button>
                @endforeach
            </div>
        @endif
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
