@extends('shop.layouts.app')

@section('title', ($product->meta_title ?: $product->name).' — '.config('app.name'))
@section('meta_description', $product->meta_description ?: $product->short_description)

@section('content')
<div class="max-w-6xl mx-auto px-4 sm:px-6 py-8 sm:py-10">
    <div class="grid lg:grid-cols-2 gap-8 lg:gap-10">
        <div>
            <div class="rounded-2xl overflow-hidden bg-cream-dark aspect-square mb-3 shadow-md shadow-stone-900/5">
                <img id="main-image" src="{{ $defaultVariant?->imageUrl() ?: $product->primaryImageUrl() }}" alt="{{ $product->name }}" class="w-full h-full object-cover">
            </div>
            <div class="flex gap-2 overflow-x-auto">
                @foreach($product->images as $image)
                    <button type="button" class="thumb w-16 h-16 rounded-lg overflow-hidden border border-[var(--line)]" data-src="{{ $image->url() }}">
                        <img src="{{ $image->url() }}" alt="" class="w-full h-full object-cover">
                    </button>
                @endforeach
            </div>
        </div>
        <div>
            <p class="text-sm text-stone-500 mb-2">{{ $product->category?->name }}</p>
            <h1 class="font-display text-3xl sm:text-4xl tracking-tight text-stone-900">{{ $product->name }}</h1>
            <div class="mt-4 flex items-baseline gap-3">
                <span id="variant-price" class="text-2xl font-semibold text-brand">{{ $defaultVariant?->formattedPrice() ?: $product->formattedPrice() }}</span>
                <span id="variant-compare" class="text-stone-400 line-through {{ $defaultVariant?->compare_price ? '' : 'hidden' }}">
                    @if($defaultVariant?->compare_price) ₹{{ number_format($defaultVariant->compare_price, 2) }} @endif
                </span>
            </div>
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
                                    data-price="{{ $variant->formattedPrice() }}"
                                    data-compare="{{ $variant->compare_price ? '₹'.number_format($variant->compare_price, 2) : '' }}"
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

  document.querySelectorAll('.thumb').forEach(function (btn) {
    btn.addEventListener('click', function () {
      document.getElementById('main-image').src = btn.getAttribute('data-src');
    });
  });

  document.querySelectorAll('.variant-btn').forEach(function (btn) {
    btn.addEventListener('click', function () {
      document.querySelectorAll('.variant-btn').forEach(function (b) {
        b.className = 'variant-btn rounded-lg border px-3 py-2 text-sm border-slate-300';
      });
      btn.className = 'variant-btn rounded-lg border px-3 py-2 text-sm border-brand bg-cream-dark text-brand';
      variantId = parseInt(btn.getAttribute('data-id'), 10);
      document.getElementById('variant-price').textContent = btn.getAttribute('data-price');
      var compare = btn.getAttribute('data-compare');
      var compareEl = document.getElementById('variant-compare');
      if (compare) { compareEl.textContent = compare; compareEl.classList.remove('hidden'); }
      else { compareEl.classList.add('hidden'); }
      var active = btn.getAttribute('data-active') === '1';
      var stockEl = document.getElementById('variant-stock');
      stockEl.textContent = active ? (btn.getAttribute('data-stock') + ' in stock') : 'Out of stock';
      stockEl.className = 'mt-2 text-sm ' + (active ? 'text-emerald-700' : 'text-rose-600');
      if (btn.getAttribute('data-image')) {
        document.getElementById('main-image').src = btn.getAttribute('data-image');
      }
    });
  });

  document.getElementById('add-to-cart')?.addEventListener('click', function () {
    var qty = parseInt(document.getElementById('qty').value, 10) || 1;
    AppAjax.request(@json(route('shop.cart.store')), {
      method: 'POST',
      body: { product_id: productId, variant_id: variantId, quantity: qty }
    }).then(function (result) {
      if (result.ok && result.data.data) AppAjax.updateCartBadge(result.data.data.item_count);
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
