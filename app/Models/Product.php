<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Support\MediaUrl;
use Illuminate\Support\Str;

#[Fillable([
    'category_id', 'name', 'brand', 'slug', 'sku', 'hsn_code', 'tax_class',
    'short_description', 'description', 'price', 'compare_price', 'stock', 'image',
    'is_featured', 'is_active', 'allow_cod', 'allow_online',
    'weight', 'length', 'breadth', 'height', 'meta_title', 'meta_description', 'has_variants',
])]
class Product extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'compare_price' => 'decimal:2',
            'stock' => 'integer',
            'is_featured' => 'boolean',
            'is_active' => 'boolean',
            'allow_cod' => 'boolean',
            'allow_online' => 'boolean',
            'has_variants' => 'boolean',
            'weight' => 'decimal:3',
            'length' => 'decimal:2',
            'breadth' => 'decimal:2',
            'height' => 'decimal:2',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Product $product) {
            if (empty($product->slug)) {
                $product->slug = Str::slug($product->name);
            }
        });
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function options(): HasMany
    {
        return $this->hasMany(ProductOption::class)->orderBy('sort_order');
    }

    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class)->orderBy('sort_order');
    }

    public function offers(): HasMany
    {
        return $this->hasMany(ProductOffer::class)->orderByDesc('id');
    }

    public function activeOffer(): ?ProductOffer
    {
        return $this->offers->first(fn (ProductOffer $offer) => $offer->isRunning());
    }

    public function hasRunningOffer(): bool
    {
        return (bool) $this->activeOffer();
    }

    /** Price after the running offer, falling back to the plain price. */
    public function offerPrice(): float
    {
        return $this->activeOffer()?->applyTo($this->minPrice()) ?? $this->minPrice();
    }

    public function formattedOfferPrice(): string
    {
        return '₹'.number_format($this->offerPrice(), 2);
    }

    public function defaultVariant(): ?ProductVariant
    {
        return $this->variants->firstWhere('is_default', true)
            ?? $this->variants->firstWhere('is_active', true)
            ?? $this->variants->first();
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    public function inStock(): bool
    {
        if ($this->relationLoaded('variants') || $this->variants()->exists()) {
            return $this->variants->where('is_active', true)->sum('stock') > 0;
        }

        return $this->stock > 0;
    }

    public function minPrice(): float
    {
        $variant = $this->variants->where('is_active', true)->sortBy('price')->first();

        return (float) ($variant?->price ?? $this->price);
    }

    public function formattedPrice(): string
    {
        return '₹'.number_format($this->minPrice(), 2);
    }

    public function primaryImageUrl(): ?string
    {
        $image = $this->images->firstWhere('is_primary', true) ?? $this->images->first();

        if ($image) {
            return $image->url();
        }

        if (! $this->image) {
            return null;
        }

        if (str_starts_with($this->image, 'http')) {
            return $this->image;
        }
        return MediaUrl::public($this->image);
    }
}
