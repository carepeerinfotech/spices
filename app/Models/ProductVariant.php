<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Support\MediaUrl;

#[Fillable([
    'product_id', 'sku', 'name', 'option_label', 'option_values',
    'price', 'compare_price', 'stock', 'image',
    'weight', 'length', 'breadth', 'height', 'is_default', 'is_active',
])]
class ProductVariant extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'option_values' => 'array',
            'price' => 'decimal:2',
            'compare_price' => 'decimal:2',
            'stock' => 'integer',
            'weight' => 'decimal:3',
            'length' => 'decimal:2',
            'breadth' => 'decimal:2',
            'height' => 'decimal:2',
            'is_default' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function inStock(): bool
    {
        return $this->is_active && $this->stock > 0;
    }

    public function formattedPrice(): string
    {
        return '₹'.number_format((float) $this->price, 2);
    }

    public function imageUrl(): ?string
    {
        if (! $this->image) {
            return $this->product?->primaryImageUrl();
        }

        if (str_starts_with($this->image, 'http')) {
            return $this->image;
        }

        return MediaUrl::public($this->image);
    }

    public function shippingWeight(): float
    {
        return (float) ($this->weight ?: $this->product?->weight ?: 0.5);
    }
}
