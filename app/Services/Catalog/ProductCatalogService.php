<?php

namespace App\Services\Catalog;

use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductOption;
use App\Models\ProductVariant;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProductCatalogService
{
    public function save(Product $product, array $data, array $options = [], array $variants = [], array $uploads = [], array $existingImageIds = []): Product
    {
        return DB::transaction(function () use ($product, $data, $options, $variants, $uploads, $existingImageIds) {
            $isNew = ! $product->exists;

            $product->fill($data);
            $product->slug = $data['slug'] ?: Str::slug($data['name']);
            $product->has_variants = count($variants) > 1 || (! empty($options));
            $product->save();

            $this->syncOptions($product, $options);
            $this->syncVariants($product, $variants, $isNew);
            $this->syncImages($product, $uploads, $existingImageIds);

            // Keep product-level price/stock synced from default variant
            $default = $product->variants()->where('is_default', true)->first()
                ?? $product->variants()->orderBy('id')->first();

            if ($default) {
                $product->update([
                    'sku' => $default->sku,
                    'price' => $default->price,
                    'compare_price' => $default->compare_price,
                    'stock' => $product->variants()->sum('stock'),
                    'image' => $default->image ?: $product->image,
                ]);
            }

            return $product->fresh(['options.values', 'variants', 'images', 'category']);
        });
    }

    private function syncOptions(Product $product, array $options): void
    {
        $product->options()->each(function (ProductOption $option) {
            $option->values()->delete();
            $option->delete();
        });

        foreach (array_values($options) as $index => $optionData) {
            $name = trim((string) ($optionData['name'] ?? ''));
            if ($name === '') {
                continue;
            }

            $option = $product->options()->create([
                'name' => $name,
                'sort_order' => $index,
            ]);

            $rawValues = (array) ($optionData['values'] ?? []);
            $values = [];
            foreach ($rawValues as $raw) {
                foreach (explode(',', (string) $raw) as $piece) {
                    $piece = trim($piece);
                    if ($piece !== '') {
                        $values[] = $piece;
                    }
                }
            }
            foreach (array_values($values) as $vIndex => $value) {
                $option->values()->create([
                    'value' => $value,
                    'sort_order' => $vIndex,
                ]);
            }
        }
    }

    private function syncVariants(Product $product, array $variants, bool $isNew): void
    {
        $keptIds = [];

        if (empty($variants)) {
            $sku = $product->sku ?: ('SKU-'.$product->id);
            $variant = $product->variants()->updateOrCreate(
                ['is_default' => true],
                [
                    'sku' => $sku,
                    'name' => $product->name,
                    'option_label' => 'Default',
                    'option_values' => [],
                    'price' => $product->price ?: 0,
                    'compare_price' => $product->compare_price,
                    'stock' => $product->stock ?: 0,
                    'weight' => $product->weight,
                    'length' => $product->length,
                    'breadth' => $product->breadth,
                    'height' => $product->height,
                    'is_default' => true,
                    'is_active' => true,
                ]
            );
            $keptIds[] = $variant->id;
        } else {
            $hasDefault = false;
            foreach (array_values($variants) as $index => $variantData) {
                $sku = trim((string) ($variantData['sku'] ?? ''));
                if ($sku === '') {
                    continue;
                }

                $isDefault = ! empty($variantData['is_default']) || (! $hasDefault && $index === 0);
                if ($isDefault) {
                    $hasDefault = true;
                }

                $payload = [
                    'sku' => $sku,
                    'name' => $variantData['name'] ?? $product->name,
                    'option_label' => $variantData['option_label'] ?? null,
                    'option_values' => $variantData['option_values'] ?? [],
                    'price' => (float) ($variantData['price'] ?? 0),
                    'compare_price' => $variantData['compare_price'] ?? null,
                    'stock' => (int) ($variantData['stock'] ?? 0),
                    'weight' => $variantData['weight'] ?? $product->weight,
                    'length' => $variantData['length'] ?? $product->length,
                    'breadth' => $variantData['breadth'] ?? $product->breadth,
                    'height' => $variantData['height'] ?? $product->height,
                    'is_default' => $isDefault,
                    'is_active' => array_key_exists('is_active', $variantData)
                        ? filter_var($variantData['is_active'], FILTER_VALIDATE_BOOLEAN)
                        : true,
                ];

                if (! empty($variantData['id'])) {
                    $variant = $product->variants()->where('id', $variantData['id'])->first();
                    if ($variant) {
                        $variant->update($payload);
                        $keptIds[] = $variant->id;
                        continue;
                    }
                }

                $variant = $product->variants()->create($payload);
                $keptIds[] = $variant->id;
            }
        }

        if (! empty($keptIds)) {
            $product->variants()->whereNotIn('id', $keptIds)->delete();
            // Ensure only one default
            $default = $product->variants()->whereIn('id', $keptIds)->where('is_default', true)->first()
                ?? $product->variants()->whereIn('id', $keptIds)->first();
            if ($default) {
                $product->variants()->where('id', '!=', $default->id)->update(['is_default' => false]);
                $default->update(['is_default' => true]);
            }
        }
    }

    private function syncImages(Product $product, array $uploads, array $existingImageIds): void
    {
        if (! empty($existingImageIds)) {
            $product->images()->whereNotIn('id', $existingImageIds)->each(function (ProductImage $image) {
                $this->deleteStoredFile($image->path);
                $image->delete();
            });
        }

        foreach ($uploads as $index => $file) {
            if (! $file instanceof UploadedFile) {
                continue;
            }

            $path = $file->store('products/'.$product->id, 'public');
            $product->images()->create([
                'path' => $path,
                'alt' => $product->name,
                'sort_order' => $product->images()->count() + $index,
                'is_primary' => $product->images()->count() === 0 && $index === 0,
            ]);
        }

        if (! $product->images()->where('is_primary', true)->exists()) {
            $first = $product->images()->orderBy('sort_order')->first();
            if ($first) {
                $first->update(['is_primary' => true]);
                $product->update(['image' => $first->path]);
            }
        } else {
            $primary = $product->images()->where('is_primary', true)->first();
            if ($primary) {
                $product->update(['image' => $primary->path]);
            }
        }
    }

    public function setPrimaryImage(Product $product, int $imageId): void
    {
        $product->images()->update(['is_primary' => false]);
        $image = $product->images()->where('id', $imageId)->firstOrFail();
        $image->update(['is_primary' => true]);
        $product->update(['image' => $image->path]);
    }

    private function deleteStoredFile(?string $path): void
    {
        if ($path && ! str_starts_with($path, 'http') && Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }
}
