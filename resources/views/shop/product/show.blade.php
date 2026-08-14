@extends('shop.layouts.app')

@section('title', ($product->meta_title ?: $product->name).' — '.config('app.name'))
@section('meta_description', $product->meta_description ?: $product->short_description)

@section('content')
<x-shop.breadcrumb :title="$product->name" />

<div class="max-w-6xl mx-auto px-4 sm:px-6 py-8 sm:py-10">
    @php
        $mainSrc = $defaultVariant?->imageUrl() ?: $product->primaryImageUrl();
        $activeImageIndex = $product->images->search(fn ($img) => $img->url() === $mainSrc);
        if ($activeImageIndex === false) $activeImageIndex = 0;
    @endphp
    <div class="grid lg:grid-cols-2 gap-8 lg:gap-10">
        <div>
            <div class="gallery-viewport relative rounded-2xl overflow-hidden bg-cream-dark aspect-square mb-3 shadow-md shadow-stone-900/5" id="gallery-viewport">
                <div class="gallery-zoom" id="gallery-zoom">
                    <img id="main-image" src="{{ $mainSrc }}" alt="{{ $product->name }}" class="gallery-image w-full h-full object-cover">
                </div>
                @if($product->images->count() > 1)
                    <button type="button" id="gallery-prev" class="gallery-nav gallery-nav--prev" aria-label="Previous image">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m15 18-6-6 6-6"/></svg>
                    </button>
                    <button type="button" id="gallery-next" class="gallery-nav gallery-nav--next" aria-label="Next image">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m9 18 6-6-6-6"/></svg>
                    </button>
                @endif
                <button type="button" id="gallery-zoom-btn" class="gallery-zoom-btn" aria-label="Zoom image">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/><path d="M11 8v6M8 11h6"/></svg>
                </button>
            </div>
            @if($product->images->count() > 1)
                <div class="flex gap-2 overflow-x-auto" id="gallery-thumbs">
                    @foreach($product->images as $image)
                        <button type="button" class="thumb w-16 h-16 rounded-lg overflow-hidden border {{ $loop->index === $activeImageIndex ? 'border-brand' : 'border-[var(--line)]' }}" data-index="{{ $loop->index }}">
                            <img src="{{ $image->url() }}" alt="" class="w-full h-full object-cover">
                        </button>
                    @endforeach
                </div>
            @endif
        </div>
        <div>
            <p class="text-sm text-stone-500 mb-2">{{ $product->category?->name }}</p>
            <h1 class="font-display text-3xl sm:text-4xl tracking-tight text-stone-900">{{ $product->name }}</h1>
            @php
                $offer = $product->activeOffer();
                $basePrice = (float) ($defaultVariant?->price ?? $product->price);
                $offerPrice = $offer ? $offer->applyTo($basePrice) : $basePrice;
            @endphp
            <div class="mt-4 flex items-baseline gap-3">
                <span id="variant-price" class="text-2xl font-semibold text-brand">₹{{ number_format($offerPrice, 2) }}</span>
                <span id="variant-was" class="text-stone-400 line-through {{ $offer ? '' : 'hidden' }}">
                    @if($offer) ₹{{ number_format($basePrice, 2) }} @endif
                </span>
                <span id="variant-compare" class="text-stone-400 line-through {{ (! $offer && $defaultVariant?->compare_price) ? '' : 'hidden' }}">
                    @if(! $offer && $defaultVariant?->compare_price) ₹{{ number_format($defaultVariant->compare_price, 2) }} @endif
                </span>
            </div>
            @if($offer)
                <div class="mt-2 flex flex-wrap items-center gap-2">
                    <span class="inline-flex items-center rounded-full bg-emerald-50 text-emerald-800 border border-emerald-200 px-2.5 py-1 text-xs font-medium">
                        @if($offer->name){{ $offer->name }} · @endif{{ $offer->label() }}
                    </span>
                    <span id="variant-saving" class="text-xs text-emerald-700">You save ₹{{ number_format($basePrice - $offerPrice, 2) }}</span>
                </div>
                <p class="mt-1 text-xs text-stone-500">Offer ends {{ \App\Support\LocalTime::display($offer->ends_at) ?? 'soon' }}</p>
            @endif
            <p class="mt-4 text-stone-600 leading-relaxed">{{ $product->short_description }}</p>
            <p id="variant-stock" class="mt-2 text-sm {{ ($defaultVariant?->inStock() ?? false) ? 'text-emerald-700' : 'text-rose-600' }}">
                {{ ($defaultVariant?->inStock() ?? false) ? ($defaultVariant->stock.' in stock') : 'Out of stock' }}
            </p>

            @if($product->variants->count() > 1)
                <div class="mt-6">
                    <label class="text-sm font-medium">Variation</label>
                    <div class="mt-2 flex flex-wrap gap-2" id="variant-options">
                        @foreach($product->variants as $variant)
                            <button type="button"
                                    class="variant-btn rounded-lg border px-3 py-2 text-sm {{ $defaultVariant?->id === $variant->id ? 'border-brand bg-cream-dark text-brand' : 'border-[var(--line)]' }}"
                                    data-id="{{ $variant->id }}"
                                    data-price="₹{{ number_format($offer ? $offer->applyTo((float) $variant->price) : (float) $variant->price, 2) }}"
                                    data-was="{{ $offer ? '₹'.number_format((float) $variant->price, 2) : '' }}"
                                    data-saving="{{ $offer ? '₹'.number_format($offer->discountFor((float) $variant->price), 2) : '' }}"
                                    data-compare="{{ (! $offer && $variant->compare_price) ? '₹'.number_format($variant->compare_price, 2) : '' }}"
                                    data-stock="{{ $variant->stock }}"
                                    data-active="{{ $variant->inStock() ? 1 : 0 }}"
                                    data-image="{{ $variant->imageUrl() }}">
                                {{ $variant->option_label ?: $variant->sku }}
                            </button>
                        @endforeach
                    </div>
                </div>
            @endif

            <div class="mt-8 flex flex-wrap items-center gap-3">
                <label class="text-sm text-slate-600">Qty
                    <input id="qty" type="number" min="1" value="1" class="ml-2 w-20 rounded-lg border border-slate-300 px-2 py-2 text-sm">
                </label>
                <button type="button" id="add-to-cart" class="btn-brand">
                    Add to Cart
                </button>
            </div>

            <!-- <div class="mt-8 rounded-xl border border-slate-200 p-4">
                <h3 class="font-medium mb-2">Check delivery</h3>
                <div class="flex gap-2">
                    <input id="pincode" type="text" maxlength="6" placeholder="Enter pincode" class="rounded-lg border border-slate-300 px-3 py-2 text-sm w-40">
                    <button type="button" id="check-pincode" class="rounded-lg border border-slate-300 px-3 py-2 text-sm">Check</button>
                </div>
                <p id="pincode-result" class="text-sm text-slate-600 mt-2"></p>
            </div> -->

            <!-- <div class="mt-8 text-sm text-slate-500 space-y-1">
                <p>COD: {{ $product->allow_cod ? 'Available' : 'Not available' }}</p>
                <p>Online payment: {{ $product->allow_online ? 'Available' : 'Not available' }}</p>
            </div> -->

            <div class="mt-10 border-t border-[var(--line)]">
                <button type="button" class="accordion-trigger w-full flex items-center justify-between py-4 cursor-pointer" aria-expanded="true" aria-controls="description-content">
                    <span class="font-display text-lg text-stone-900">Description</span>
                    <svg class="accordion-chevron w-4 h-4 text-stone-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m6 9 6 6 6-6"/></svg>
                </button>
                <div id="description-content" class="accordion-content is-open">
                    <div class="accordion-inner">
                        <div class="prose prose-slate max-w-none text-slate-600 text-sm leading-relaxed pb-4">
                            {!! nl2br(e($product->description)) !!}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="gallery-lightbox" id="gallery-lightbox" aria-hidden="true">
    <button type="button" class="gallery-lightbox__close" id="gallery-lightbox-close" aria-label="Close">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6 6 18M6 6l12 12"/></svg>
    </button>
    @if($product->images->count() > 1)
        <button type="button" id="gallery-lightbox-prev" class="gallery-nav gallery-nav--prev" aria-label="Previous image">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m15 18-6-6 6-6"/></svg>
        </button>
        <button type="button" id="gallery-lightbox-next" class="gallery-nav gallery-nav--next" aria-label="Next image">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m9 18 6-6-6-6"/></svg>
        </button>
    @endif
    <img id="gallery-lightbox-image" src="" alt="{{ $product->name }}">
</div>
@endsection

@push('scripts')
<script>
(function () {
  var productId = {{ $product->id }};
  var variantId = {{ $defaultVariant?->id ?? 'null' }};

  document.querySelectorAll('.accordion-trigger').forEach(function (trigger) {
    trigger.addEventListener('click', function () {
      var expanded = trigger.getAttribute('aria-expanded') === 'true';
      var content = document.getElementById(trigger.getAttribute('aria-controls'));
      trigger.setAttribute('aria-expanded', String(!expanded));
      if (content) content.classList.toggle('is-open', !expanded);
    });
  });

  // Gallery: carousel navigation, thumbnails, zoom and lightbox.
  var galleryImages = @json($product->images->map(fn ($img) => $img->url())->values());
  if (!galleryImages.length) {
    var initialSrc = document.getElementById('main-image')?.getAttribute('src');
    if (initialSrc) galleryImages = [initialSrc];
  }
  var currentImageIndex = {{ (int) $activeImageIndex }};
  if (currentImageIndex < 0 || currentImageIndex >= galleryImages.length) currentImageIndex = 0;

  var lightbox = document.getElementById('gallery-lightbox');
  var lightboxImage = document.getElementById('gallery-lightbox-image');
  var mainImage = document.getElementById('main-image');
  var hoverCapable = window.matchMedia('(hover: hover)').matches;

  function renderGalleryImage(index) {
    if (!galleryImages[index]) return;
    currentImageIndex = index;
    mainImage.src = galleryImages[index];
    document.querySelectorAll('#gallery-thumbs .thumb').forEach(function (thumb, i) {
      thumb.className = 'thumb w-16 h-16 rounded-lg overflow-hidden border ' + (i === index ? 'border-brand' : 'border-[var(--line)]');
    });
    if (lightbox?.classList.contains('is-open')) lightboxImage.src = galleryImages[index];
  }

  function showUnlistedImage(src) {
    mainImage.src = src;
    document.querySelectorAll('#gallery-thumbs .thumb').forEach(function (thumb) {
      thumb.className = 'thumb w-16 h-16 rounded-lg overflow-hidden border border-[var(--line)]';
    });
  }

  document.querySelectorAll('#gallery-thumbs .thumb').forEach(function (thumb) {
    thumb.addEventListener('click', function () {
      renderGalleryImage(parseInt(thumb.getAttribute('data-index'), 10));
    });
  });

  document.getElementById('gallery-prev')?.addEventListener('click', function () {
    renderGalleryImage((currentImageIndex - 1 + galleryImages.length) % galleryImages.length);
  });
  document.getElementById('gallery-next')?.addEventListener('click', function () {
    renderGalleryImage((currentImageIndex + 1) % galleryImages.length);
  });

  var zoomWrap = document.getElementById('gallery-zoom');
  if (zoomWrap && hoverCapable) {
    zoomWrap.addEventListener('mousemove', function (e) {
      var rect = zoomWrap.getBoundingClientRect();
      var x = ((e.clientX - rect.left) / rect.width) * 100;
      var y = ((e.clientY - rect.top) / rect.height) * 100;
      mainImage.style.transformOrigin = x + '% ' + y + '%';
    });
    zoomWrap.addEventListener('mouseenter', function () { zoomWrap.classList.add('is-zooming'); });
    zoomWrap.addEventListener('mouseleave', function () { zoomWrap.classList.remove('is-zooming'); });
  }

  function openLightbox() {
    if (!lightbox) return;
    lightboxImage.src = galleryImages[currentImageIndex] || mainImage.src;
    lightbox.classList.add('is-open');
    lightbox.setAttribute('aria-hidden', 'false');
    document.body.style.overflow = 'hidden';
  }
  function closeLightbox() {
    if (!lightbox) return;
    lightbox.classList.remove('is-open');
    lightbox.setAttribute('aria-hidden', 'true');
    document.body.style.overflow = '';
  }
  document.getElementById('gallery-zoom-btn')?.addEventListener('click', openLightbox);
  zoomWrap?.addEventListener('click', function () {
    if (!hoverCapable) openLightbox();
  });
  document.getElementById('gallery-lightbox-close')?.addEventListener('click', closeLightbox);
  lightbox?.addEventListener('click', function (e) {
    if (e.target === lightbox) closeLightbox();
  });
  document.getElementById('gallery-lightbox-prev')?.addEventListener('click', function () {
    renderGalleryImage((currentImageIndex - 1 + galleryImages.length) % galleryImages.length);
  });
  document.getElementById('gallery-lightbox-next')?.addEventListener('click', function () {
    renderGalleryImage((currentImageIndex + 1) % galleryImages.length);
  });
  document.addEventListener('keydown', function (e) {
    if (!lightbox || !lightbox.classList.contains('is-open')) return;
    if (e.key === 'Escape') closeLightbox();
    if (e.key === 'ArrowLeft') renderGalleryImage((currentImageIndex - 1 + galleryImages.length) % galleryImages.length);
    if (e.key === 'ArrowRight') renderGalleryImage((currentImageIndex + 1) % galleryImages.length);
  });

  document.querySelectorAll('.variant-btn').forEach(function (btn) {
    btn.addEventListener('click', function () {
      document.querySelectorAll('.variant-btn').forEach(function (b) {
        b.className = 'variant-btn rounded-lg border px-3 py-2 text-sm border-[var(--line)]';
      });
      btn.className = 'variant-btn rounded-lg border px-3 py-2 text-sm border-brand bg-cream-dark text-brand';
      variantId = parseInt(btn.getAttribute('data-id'), 10);
      document.getElementById('variant-price').textContent = btn.getAttribute('data-price');

      function setText(id, value) {
        var el = document.getElementById(id);
        if (!el) return;
        if (value) { el.textContent = value; el.classList.remove('hidden'); }
        else { el.classList.add('hidden'); }
      }

      setText('variant-was', btn.getAttribute('data-was'));
      setText('variant-compare', btn.getAttribute('data-compare'));
      var saving = btn.getAttribute('data-saving');
      setText('variant-saving', saving ? 'You save ' + saving : '');
      var active = btn.getAttribute('data-active') === '1';
      var stockEl = document.getElementById('variant-stock');
      stockEl.textContent = active ? (btn.getAttribute('data-stock') + ' in stock') : 'Out of stock';
      stockEl.className = 'mt-2 text-sm ' + (active ? 'text-emerald-700' : 'text-rose-600');
      var variantImage = btn.getAttribute('data-image');
      if (variantImage) {
        var listedIndex = galleryImages.indexOf(variantImage);
        if (listedIndex !== -1) renderGalleryImage(listedIndex);
        else showUnlistedImage(variantImage);
      }
    });
  });

  document.getElementById('add-to-cart')?.addEventListener('click', function () {
    var qty = parseInt(document.getElementById('qty').value, 10) || 1;
    AppAjax.request(@json(route('shop.cart.store')), {
      method: 'POST',
      body: { product_id: productId, variant_id: variantId, quantity: qty }
    }).then(function (result) {
      if (result.ok && result.data.data && window.CartDrawer) {
        CartDrawer.render(result.data.data);
        CartDrawer.open();
      }
    });
  });

  document.getElementById('check-pincode')?.addEventListener('click', function () {
    var pincode = document.getElementById('pincode').value.trim();
    var qty = parseInt(document.getElementById('qty').value, 10) || 1;
    AppAjax.request(@json(route('shipping.quote.product')), {
      method: 'POST',
      body: { product_id: productId, variant_id: variantId, pincode: pincode, qty: qty },
      toast: false
    }).then(function (result) {
      var el = document.getElementById('pincode-result');
      if (!result.ok || !result.data.success) {
        el.textContent = (result.data && result.data.message) || 'Not serviceable.';
        el.className = 'text-sm text-rose-600 mt-2';
        return;
      }
      var c = result.data.cheapest;
      el.textContent = 'Shipping ₹' + Number(c.rate).toFixed(2) + ' · Estimated delivery in ' + c.etd + ' days via ' + c.courier_name;
      el.className = 'text-sm text-emerald-700 mt-2';
    });
  });
})();
</script>
@endpush
