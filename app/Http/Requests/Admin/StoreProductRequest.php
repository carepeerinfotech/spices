<?php

namespace App\Http\Requests\Admin;

use App\Models\Product;
use App\Services\Media\ImageService;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $productId = $this->route('product')?->id;

        return [
            'category_id' => ['nullable', 'exists:categories,id'],
            'name' => ['required', 'string', 'max:255'],
            'brand' => ['nullable', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', Rule::unique('products', 'slug')->ignore($productId)],
            'sku' => ['required', 'string', 'max:100', Rule::unique('products', 'sku')->ignore($productId)],
            'hsn_code' => ['nullable', 'string', 'max:20'],
            'tax_class' => ['nullable', 'string', 'max:50'],
            'short_description' => ['nullable', 'string', 'max:500'],
            'description' => ['nullable', 'string'],
            'price' => ['required', 'numeric', 'min:0'],
            'compare_price' => ['nullable', 'numeric', 'min:0'],
            'stock' => ['nullable', 'integer', 'min:0'],
            'is_featured' => ['sometimes', 'boolean'],
            'is_active' => ['sometimes', 'boolean'],
            'allow_cod' => ['sometimes', 'boolean'],
            'allow_online' => ['sometimes', 'boolean'],
            'weight' => ['nullable', 'numeric', 'min:0'],
            'length' => ['nullable', 'numeric', 'min:0'],
            'breadth' => ['nullable', 'numeric', 'min:0'],
            'height' => ['nullable', 'numeric', 'min:0'],
            'meta_title' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string', 'max:500'],
            'options' => ['nullable', 'array'],
            'options.*.name' => ['nullable', 'string', 'max:100'],
            'options.*.values' => ['nullable', 'array'],
            'variants' => ['nullable', 'array'],
            'variants.*.id' => ['nullable', 'integer'],
            'variants.*.sku' => ['required_with:variants', 'string', 'max:100'],
            'variants.*.price' => ['required_with:variants', 'numeric', 'min:0'],
            'variants.*.stock' => ['required_with:variants', 'integer', 'min:0'],
            'variants.*.option_label' => ['nullable', 'string', 'max:255'],
            'variants.*.is_default' => ['sometimes', 'boolean'],
            'variants.*.is_active' => ['sometimes', 'boolean'],
            'offers' => ['nullable', 'array'],
            'offers.*.id' => ['nullable', 'integer'],
            'offers.*.name' => ['nullable', 'string', 'max:255'],
            'offers.*.discount_type' =>['required_with:offers', Rule::in(['flat', 'percentage'])],
            'offers.*.value' => ['required_with:offers', 'numeric', 'min:0'],
            'offers.*.apply_to_category' => ['sometimes', 'boolean'],
            'offers.*.starts_at' => ['required_with:offers', 'date'],
            'offers.*.ends_at' => ['required_with:offers', 'date', 'after_or_equal:offers.*.starts_at'],
            // Upload rules come from config/media.php; removal and ordering are
            // applied instantly through the images endpoints, not on save.
        ] + app(ImageService::class)->rules(Product::class);
    }

    public function attributes(): array
    {
        return [
            'offers.*.starts_at' => 'offer start date',
            'offers.*.ends_at' => 'offer end date',
            'offers.*.value' => 'offer value',
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {
                foreach ((array) $this->input('offers', []) as $index => $offer) {
                    if (($offer['discount_type'] ?? null) === 'percentage' && (float) ($offer['value'] ?? 0) > 100) {
                        $validator->errors()->add("offers.$index.value", 'A percentage discount cannot exceed 100.');
                    }
                }
            },
        ];
    }
}
