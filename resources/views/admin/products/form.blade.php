@extends('admin.layouts.app')

@section('title', $product->exists ? 'Edit Product' : 'Add Product')
@section('heading', $product->exists ? 'Edit product' : 'Add product')
@section('subtitle', 'Manage details, variations, media, shipping and purchase modes')

@section('content')
<form data-ajax="formdata" method="POST" enctype="multipart/form-data"
      action="{{ $product->exists ? route('admin.products.update', $product) : route('admin.products.store') }}"
      class="rounded-xl bg-white border border-slate-200 overflow-hidden" id="product-form">
    @csrf
    @if($product->exists) @method('PUT') @endif

    <div class="border-b border-slate-200 px-4 pt-3 flex flex-wrap gap-2" role="tablist">
        @foreach(['basic' => 'Basic Info', 'variants' => 'Variations & Pricing', 'media' => 'Media', 'shipping' => 'Shipping', 'purchase' => 'Purchase Modes', 'seo' => 'SEO / Status'] as $key => $label)
            <button type="button" data-tab="{{ $key }}" role="tab"
                    class="tab-btn px-3 py-2 text-sm rounded-t-lg border border-b-0 {{ $key === 'basic' ? 'bg-slate-50 border-slate-200 text-teal-800 font-medium' : 'border-transparent text-slate-500' }}">
                {{ $label }}
            </button>
        @endforeach
    </div>

    <div class="p-6 space-y-5">
        <div data-tab-panel="basic" class="space-y-4">
            <div class="grid sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium mb-1.5">Name</label>
                    <input name="name" required value="{{ old('name', $product->name) }}" class="w-full rounded-lg border border-slate-300 px-3 py-2">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1.5">Brand</label>
                    <input name="brand" value="{{ old('brand', $product->brand) }}" class="w-full rounded-lg border border-slate-300 px-3 py-2">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1.5">Slug</label>
                    <input name="slug" value="{{ old('slug', $product->slug) }}" class="w-full rounded-lg border border-slate-300 px-3 py-2">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1.5">Base SKU</label>
                    <input name="sku" required value="{{ old('sku', $product->sku) }}" class="w-full rounded-lg border border-slate-300 px-3 py-2">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1.5">Category</label>
                    <select name="category_id" class="w-full rounded-lg border border-slate-300 px-3 py-2">
                        <option value="">— None —</option>
                        @foreach($categories as $category)
                            <option value="{{ $category->id }}" @selected(old('category_id', $product->category_id) == $category->id)>{{ $category->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1.5">HSN Code</label>
                    <input name="hsn_code" value="{{ old('hsn_code', $product->hsn_code) }}" class="w-full rounded-lg border border-slate-300 px-3 py-2">
                </div>
            </div>
            <div>
                <label class="block text-sm font-medium mb-1.5">Short description</label>
                <input name="short_description" value="{{ old('short_description', $product->short_description) }}" class="w-full rounded-lg border border-slate-300 px-3 py-2">
            </div>
            <div>
                <label class="block text-sm font-medium mb-1.5">Description</label>
                <textarea name="description" rows="5" class="w-full rounded-lg border border-slate-300 px-3 py-2">{{ old('description', $product->description) }}</textarea>
            </div>
            <div class="grid sm:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium mb-1.5">Base price (INR)</label>
                    <input name="price" type="number" step="0.01" min="0" required value="{{ old('price', $product->price ?? 0) }}" class="w-full rounded-lg border border-slate-300 px-3 py-2">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1.5">Compare price</label>
                    <input name="compare_price" type="number" step="0.01" min="0" value="{{ old('compare_price', $product->compare_price) }}" class="w-full rounded-lg border border-slate-300 px-3 py-2">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1.5">Base stock</label>
                    <input name="stock" type="number" min="0" value="{{ old('stock', $product->stock ?? 0) }}" class="w-full rounded-lg border border-slate-300 px-3 py-2">
                </div>
            </div>
        </div>

        <div data-tab-panel="variants" class="hidden space-y-4">
            <div id="options-wrap" class="space-y-3">
                <div class="flex items-center justify-between">
                    <h3 class="font-medium">Options (e.g. Size, Color)</h3>
                    <button type="button" id="add-option" class="text-sm text-teal-700">+ Add option</button>
                </div>
                <div id="options-list" class="space-y-3">
                    @forelse(($product->options ?? collect()) as $oIndex => $option)
                        <div class="option-row rounded-lg border border-slate-200 p-3 grid sm:grid-cols-2 gap-3">
                            <input name="options[{{ $oIndex }}][name]" value="{{ $option->name }}" placeholder="Option name" class="rounded-lg border border-slate-300 px-3 py-2">
                            <input name="options[{{ $oIndex }}][values][]" value="{{ $option->values->pluck('value')->join(',') }}" placeholder="Values comma separated" class="rounded-lg border border-slate-300 px-3 py-2 option-values">
                        </div>
                    @empty
                        <div class="option-row rounded-lg border border-slate-200 p-3 grid sm:grid-cols-2 gap-3">
                            <input name="options[0][name]" placeholder="Option name (Size)" class="rounded-lg border border-slate-300 px-3 py-2">
                            <input name="options[0][values][]" placeholder="S,M,L" class="rounded-lg border border-slate-300 px-3 py-2 option-values">
                        </div>
                    @endforelse
                </div>
                <button type="button" id="generate-variants" class="rounded-lg border border-slate-300 px-3 py-2 text-sm">Generate combinations</button>
            </div>

            <div>
                <h3 class="font-medium mb-2">Variants</h3>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm" id="variants-table">
                        <thead class="bg-slate-50 text-left text-slate-500">
                        <tr>
                            <th class="px-3 py-2">Label</th>
                            <th class="px-3 py-2">SKU</th>
                            <th class="px-3 py-2">Price</th>
                            <th class="px-3 py-2">Stock</th>
                            <th class="px-3 py-2">Default</th>
                            <th class="px-3 py-2">Active</th>
                        </tr>
                        </thead>
                        <tbody id="variants-body">
                        @forelse(($product->variants ?? collect()) as $vIndex => $variant)
                            <tr>
                                <td class="px-3 py-2">
                                    <input type="hidden" name="variants[{{ $vIndex }}][id]" value="{{ $variant->id }}">
                                    <input name="variants[{{ $vIndex }}][option_label]" value="{{ $variant->option_label }}" class="w-36 rounded border border-slate-300 px-2 py-1">
                                </td>
                                <td class="px-3 py-2"><input name="variants[{{ $vIndex }}][sku]" value="{{ $variant->sku }}" class="w-36 rounded border border-slate-300 px-2 py-1"></td>
                                <td class="px-3 py-2"><input name="variants[{{ $vIndex }}][price]" type="number" step="0.01" value="{{ $variant->price }}" class="w-24 rounded border border-slate-300 px-2 py-1"></td>
                                <td class="px-3 py-2"><input name="variants[{{ $vIndex }}][stock]" type="number" value="{{ $variant->stock }}" class="w-20 rounded border border-slate-300 px-2 py-1"></td>
                                <td class="px-3 py-2"><input type="checkbox" name="variants[{{ $vIndex }}][is_default]" value="1" @checked($variant->is_default)></td>
                                <td class="px-3 py-2"><input type="checkbox" name="variants[{{ $vIndex }}][is_active]" value="1" data-bool @checked($variant->is_active)></td>
                            </tr>
                        @empty
                        @endforelse
                        </tbody>
                    </table>
                </div>
                <p class="text-xs text-slate-500 mt-2">Leave variants empty to use a single default variant from base price/stock/SKU.</p>
            </div>
        </div>

        <div data-tab-panel="media" class="hidden space-y-4">
            <div>
                <label class="block text-sm font-medium mb-1.5">Upload images</label>
                <input type="file" name="images[]" accept="image/*" multiple class="block w-full text-sm">
            </div>
            <div class="grid sm:grid-cols-4 gap-3">
                @foreach(($product->images ?? collect()) as $image)
                    <label class="border rounded-lg p-2 block">
                        <img src="{{ $image->url() }}" alt="" class="w-full h-28 object-cover rounded mb-2">
                        <input type="hidden" name="existing_images[]" value="{{ $image->id }}">
                        <span class="text-xs flex items-center gap-1">
                            <input type="radio" name="primary_image_id" value="{{ $image->id }}" @checked($image->is_primary)> Primary
                        </span>
                    </label>
                @endforeach
            </div>
        </div>

        <div data-tab-panel="shipping" class="hidden space-y-4">
            <div class="grid sm:grid-cols-4 gap-4">
                <div>
                    <label class="block text-sm font-medium mb-1.5">Weight (kg)</label>
                    <input name="weight" type="number" step="0.001" min="0" value="{{ old('weight', $product->weight) }}" class="w-full rounded-lg border border-slate-300 px-3 py-2">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1.5">Length (cm)</label>
                    <input name="length" type="number" step="0.01" min="0" value="{{ old('length', $product->length) }}" class="w-full rounded-lg border border-slate-300 px-3 py-2">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1.5">Breadth (cm)</label>
                    <input name="breadth" type="number" step="0.01" min="0" value="{{ old('breadth', $product->breadth) }}" class="w-full rounded-lg border border-slate-300 px-3 py-2">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1.5">Height (cm)</label>
                    <input name="height" type="number" step="0.01" min="0" value="{{ old('height', $product->height) }}" class="w-full rounded-lg border border-slate-300 px-3 py-2">
                </div>
            </div>
        </div>

        <div data-tab-panel="purchase" class="hidden space-y-4">
            <label class="inline-flex items-center gap-2 text-sm">
                <input type="checkbox" name="allow_cod" value="1" data-bool @checked(old('allow_cod', $product->allow_cod ?? true))> Allow Cash on Delivery
            </label>
            <label class="inline-flex items-center gap-2 text-sm block">
                <input type="checkbox" name="allow_online" value="1" data-bool @checked(old('allow_online', $product->allow_online ?? true))> Allow Online Payment (Paytm)
            </label>
            <div>
                <label class="block text-sm font-medium mb-1.5">Tax class</label>
                <select name="tax_class" class="w-full max-w-xs rounded-lg border border-slate-300 px-3 py-2">
                    <option value="gst_0" @selected(old('tax_class', $product->tax_class) === 'gst_0')>GST 0%</option>
                    <option value="gst_5" @selected(old('tax_class', $product->tax_class) === 'gst_5')>GST 5%</option>
                    <option value="gst_12" @selected(old('tax_class', $product->tax_class) === 'gst_12')>GST 12%</option>
                    <option value="gst_18" @selected(old('tax_class', $product->tax_class ?? 'gst_18') === 'gst_18')>GST 18%</option>
                    <option value="gst_28" @selected(old('tax_class', $product->tax_class) === 'gst_28')>GST 28%</option>
                </select>
            </div>
        </div>

        <div data-tab-panel="seo" class="hidden space-y-4">
            <div>
                <label class="block text-sm font-medium mb-1.5">Meta title</label>
                <input name="meta_title" value="{{ old('meta_title', $product->meta_title) }}" class="w-full rounded-lg border border-slate-300 px-3 py-2">
            </div>
            <div>
                <label class="block text-sm font-medium mb-1.5">Meta description</label>
                <textarea name="meta_description" rows="3" class="w-full rounded-lg border border-slate-300 px-3 py-2">{{ old('meta_description', $product->meta_description) }}</textarea>
            </div>
            <label class="inline-flex items-center gap-2 text-sm">
                <input type="checkbox" name="is_featured" value="1" data-bool @checked(old('is_featured', $product->is_featured))> Featured
            </label>
            <label class="inline-flex items-center gap-2 text-sm block">
                <input type="checkbox" name="is_active" value="1" data-bool @checked(old('is_active', $product->is_active ?? true))> Active
            </label>
        </div>
    </div>

    <div class="px-6 py-4 border-t border-slate-100 flex gap-3 bg-slate-50">
        <button type="submit" class="rounded-lg bg-teal-700 hover:bg-teal-600 text-white px-4 py-2 text-sm">Save product</button>
        <a href="{{ route('admin.products.index') }}" class="rounded-lg border border-slate-300 px-4 py-2 text-sm">Cancel</a>
    </div>
</form>
@endsection

@push('scripts')
<script>
(function () {
  document.querySelectorAll('.tab-btn').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var key = btn.getAttribute('data-tab');
      document.querySelectorAll('.tab-btn').forEach(function (b) {
        b.className = 'tab-btn px-3 py-2 text-sm rounded-t-lg border border-b-0 border-transparent text-slate-500';
      });
      btn.className = 'tab-btn px-3 py-2 text-sm rounded-t-lg border border-b-0 bg-slate-50 border-slate-200 text-teal-800 font-medium';
      document.querySelectorAll('[data-tab-panel]').forEach(function (panel) {
        panel.classList.toggle('hidden', panel.getAttribute('data-tab-panel') !== key);
      });
    });
  });

  var optionIndex = document.querySelectorAll('#options-list .option-row').length;
  document.getElementById('add-option')?.addEventListener('click', function () {
    var wrap = document.getElementById('options-list');
    var div = document.createElement('div');
    div.className = 'option-row rounded-lg border border-slate-200 p-3 grid sm:grid-cols-2 gap-3';
    div.innerHTML = '<input name="options[' + optionIndex + '][name]" placeholder="Option name" class="rounded-lg border border-slate-300 px-3 py-2">' +
      '<input name="options[' + optionIndex + '][values][]" placeholder="Values comma separated" class="rounded-lg border border-slate-300 px-3 py-2 option-values">';
    wrap.appendChild(div);
    optionIndex++;
  });

  function cartesian(arrays) {
    return arrays.reduce(function (acc, curr) {
      var next = [];
      acc.forEach(function (a) {
        curr.forEach(function (c) { next.push(a.concat([c])); });
      });
      return next;
    }, [[]]);
  }

  document.getElementById('generate-variants')?.addEventListener('click', function () {
    var groups = [];
    document.querySelectorAll('#options-list .option-row').forEach(function (row) {
      var name = (row.querySelector('input[name*="[name]"]')?.value || '').trim();
      var values = (row.querySelector('.option-values')?.value || '').split(',').map(function (v) { return v.trim(); }).filter(Boolean);
      if (name && values.length) groups.push(values.map(function (v) { return { name: name, value: v }; }));
    });
    if (!groups.length) { AppAjax.toast('Add option values first.', 'error'); return; }
    var combos = cartesian(groups);
    var baseSku = document.querySelector('input[name="sku"]').value || 'SKU';
    var basePrice = document.querySelector('input[name="price"]').value || '0';
    var baseStock = document.querySelector('input[name="stock"]').value || '0';
    var body = document.getElementById('variants-body');
    body.innerHTML = '';
    combos.forEach(function (combo, i) {
      var label = combo.map(function (c) { return c.value; }).join(' / ');
      var sku = baseSku + '-' + label.replace(/\s+/g, '').toUpperCase();
      var tr = document.createElement('tr');
      tr.innerHTML = '<td class="px-3 py-2"><input name="variants[' + i + '][option_label]" value="' + label + '" class="w-36 rounded border border-slate-300 px-2 py-1"></td>' +
        '<td class="px-3 py-2"><input name="variants[' + i + '][sku]" value="' + sku + '" class="w-36 rounded border border-slate-300 px-2 py-1"></td>' +
        '<td class="px-3 py-2"><input name="variants[' + i + '][price]" type="number" step="0.01" value="' + basePrice + '" class="w-24 rounded border border-slate-300 px-2 py-1"></td>' +
        '<td class="px-3 py-2"><input name="variants[' + i + '][stock]" type="number" value="' + baseStock + '" class="w-20 rounded border border-slate-300 px-2 py-1"></td>' +
        '<td class="px-3 py-2"><input type="checkbox" name="variants[' + i + '][is_default]" value="1"' + (i === 0 ? ' checked' : '') + '></td>' +
        '<td class="px-3 py-2"><input type="checkbox" name="variants[' + i + '][is_active]" value="1" data-bool checked></td>';
      body.appendChild(tr);
    });
    AppAjax.toast('Generated ' + combos.length + ' variants.', 'success');
  });
})();
</script>
@endpush
