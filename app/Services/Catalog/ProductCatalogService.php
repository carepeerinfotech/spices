<?php

namespace App\Services\Catalog;

use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductOffer;
use App\Models\ProductOption;
use App\Models\ProductVariant;
use App\Support\LocalTime;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProductCatalogService
{
    public function save(Product $product, array $data, array $options = [], array $variants = [], array $uploads = [], array $existingImageIds = [], array $offers = [], ?int $offerAuthorId = null): Product
    {
        return DB::transaction(function () use ($product, $data, $options, $variants, $uploads, $existingImageIds, $offers, $offerAuthorId) {
            $isNew = ! $product->exists;

            $product->fill($data);
            $product->slug = $data['slug'] ?: Str::slug($data['name']);
            $product->has_variants = count($variants) > 1 || (! empty($options));
            $product->save();

            $this->syncOptions($product, $options);
            $this->syncVariants($product, $variants, $isNew);
            $this->syncImages($product, $uploads, $existingImageIds);
            $this->syncOffers($product, $offers, $offerAuthorId);

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

            return $product->fresh(['options.values', 'variants', 'images', 'category', 'offers.user']);
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

    private function syncOffers(Product $product, array $offers, ?int $authorId): void
    {
        $keptIds = [];

        foreach (array_values($offers) as $offerData) {
            $value = $offerData['value'] ?? null;
            if ($value === null || $value === '') {
                continue;
            }

            $payload = [
                'name' => trim((string) ($offerData['name'] ?? '')) ?: null,
                'discount_type' => ($offerData['discount_type'] ?? 'flat') === 'percentage' ? 'percentage' : 'flat',
                'value' => (float) $value,
                'apply_to_category' => filter_var($offerData['apply_to_category'] ?? false, FILTER_VALIDATE_BOOLEAN),
                // Typed in store time; stored in UTC like every other timestamp.
                'starts_at' => LocalTime::toUtc($offerData['starts_at'] ?? null),
                'ends_at' => LocalTime::toUtc($offerData['ends_at'] ?? null),
            ];

            $offer = null;

            if (! empty($offerData['id'])) {
                $offer = $product->offers()->where('id', $offerData['id'])->first();
                if ($offer) {
                    // user_id records who created the offer, so it stays with the original author.
                    $offer->update($payload);
                }
            }

            if (! $offer) {
                $offer = $product->offers()->create($payload + ['user_id' => $authorId]);
            }

            $keptIds[] = $offer->id;
            $this->applyOfferScope($product, $offer);
        }

        $product->offers()->whereNotIn('id', $keptIds)->delete();
    }

    /**
     * Spread an offer across its category, or narrow it back to one product.
     * Editing a copy with the box still ticked pushes the change back to the
     * offer it came from and out to the whole category again.
     */
    private function applyOfferScope(Product $product, ProductOffer $offer): void
    {
        if (! $offer->source_offer_id) {
            $this->propagateOffer($product, $offer);

            return;
        }

        $root = $offer->sourceOffer;

        if (! $root) {
            $offer->update(['source_offer_id' => null]);

            return;
        }

        if (! $offer->apply_to_category) {
            // This product opts out and keeps the offer for itself.
            $offer->update(['source_offer_id' => null]);

            return;
        }

        $root->forceFill($offer->only(['name', 'discount_type', 'value', 'starts_at', 'ends_at']))
            ->forceFill(['apply_to_category' => true])
            ->save();

        $this->propagateOffer($root->product, $root);
    }

    /**
     * Mirror an offer onto every other product in the same category, and clean
     * up copies once the offer stops applying to the category.
     */
    private function propagateOffer(Product $product, ProductOffer $offer): void
    {
        if (! $offer->apply_to_category || ! $product->category_id) {
            $offer->copies()->delete();

            return;
        }

        $siblingIds = Product::where('category_id', $product->category_id)
            ->where('id', '!=', $product->id)
            ->pluck('id');

        foreach ($siblingIds as $siblingId) {
            ProductOffer::updateOrCreate(
                ['source_offer_id' => $offer->id, 'product_id' => $siblingId],
                [
                    'user_id' => $offer->user_id,
                    'name' => $offer->name,
                    'discount_type' => $offer->discount_type,
                    'value' => $offer->value,
                    'apply_to_category' => true,
                    'starts_at' => $offer->starts_at,
                    'ends_at' => $offer->ends_at,
                ]
            );
        }

        // Products that have since left the category lose their copy.
        $offer->copies()->whereNotIn('product_id', $siblingIds)->delete();
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
