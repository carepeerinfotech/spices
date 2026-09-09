@extends('admin.layouts.app')

@section('title', $coupon->exists ? 'Edit Coupon' : 'Add Coupon')
@section('heading', $coupon->exists ? 'Edit coupon' : 'Add coupon')

@section('content')
<form data-ajax="formdata" method="POST"
      action="{{ $coupon->exists ? route('admin.coupons.update', $coupon) : route('admin.coupons.store') }}"
      class="max-w-xl rounded-xl bg-white border border-slate-200 p-6 space-y-5">
    @csrf
    @if($coupon->exists) @method('PUT') @endif

    <div>
        <label class="block text-sm font-medium mb-1.5">Coupon code</label>
        <input name="code" id="coupon-code" required maxlength="50"
               pattern="[A-Za-z0-9]+" title="Letters and numbers only — no spaces or special characters."
               placeholder="e.g. FAM10" autocomplete="off"
               value="{{ old('code', $coupon->code) }}"
               class="w-full rounded-lg border border-slate-300 px-3 py-2 uppercase">
        <p class="text-xs text-slate-500 mt-1">Letters and numbers only — no spaces or special characters.</p>
        <p id="error-code" class="field-error text-xs mt-1 text-rose-600 {{ $errors->has('code') ? '' : 'hidden' }}">{{ $errors->first('code') }}</p>
    </div>

    <div class="grid sm:grid-cols-2 gap-4">
        <div>
            <label class="block text-sm font-medium mb-1.5">Discount type</label>
            <select name="discount_type" class="w-full rounded-lg border border-slate-300 px-3 py-2">
                <option value="percentage" @selected(old('discount_type', $coupon->discount_type) === 'percentage')>Percentage (%)</option>
                <option value="flat" @selected(old('discount_type', $coupon->discount_type) === 'flat')>Flat amount (₹)</option>
            </select>
        </div>
        <div>
            <label class="block text-sm font-medium mb-1.5">Value</label>
            <input name="value" type="number" min="0.01" step="0.01" required
                   value="{{ old('value', $coupon->value) }}"
                   class="w-full rounded-lg border border-slate-300 px-3 py-2">
            <p id="error-value" class="field-error text-xs mt-1 text-rose-600 {{ $errors->has('value') ? '' : 'hidden' }}">{{ $errors->first('value') }}</p>
        </div>
    </div>

    <div class="grid sm:grid-cols-2 gap-4">
        <div>
            <label class="block text-sm font-medium mb-1.5">Starts at (optional)</label>
            <input name="starts_at" type="datetime-local"
                   value="{{ old('starts_at', optional($coupon->starts_at)->format('Y-m-d\TH:i')) }}"
                   class="w-full rounded-lg border border-slate-300 px-3 py-2">
        </div>
        <div>
            <label class="block text-sm font-medium mb-1.5">Ends at (optional)</label>
            <input name="ends_at" type="datetime-local"
                   value="{{ old('ends_at', optional($coupon->ends_at)->format('Y-m-d\TH:i')) }}"
                   class="w-full rounded-lg border border-slate-300 px-3 py-2">
            <p id="error-ends_at" class="field-error text-xs mt-1 text-rose-600 {{ $errors->has('ends_at') ? '' : 'hidden' }}">{{ $errors->first('ends_at') }}</p>
        </div>
    </div>

    <label class="inline-flex items-center gap-2 text-sm">
        <input type="checkbox" name="is_active" value="1" data-bool @checked(old('is_active', $coupon->is_active ?? true))> Active
    </label>

    <div class="flex gap-3">
        <button type="submit" class="rounded-lg bg-teal-700 hover:bg-teal-600 text-white px-4 py-2 text-sm">Save</button>
        <a href="{{ route('admin.coupons.index') }}" class="rounded-lg border border-slate-300 px-4 py-2 text-sm">Cancel</a>
    </div>
</form>
@endsection

@push('scripts')
<script>
document.getElementById('coupon-code')?.addEventListener('input', function () {
    this.value = this.value.toUpperCase().replace(/[^A-Z0-9]/g, '');
});
</script>
@endpush
