<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'product_id', 'user_id', 'source_offer_id', 'name', 'discount_type', 'value',
    'apply_to_category', 'starts_at', 'ends_at',
])]
class ProductOffer extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'value' => 'decimal:2',
            'apply_to_category' => 'boolean',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function sourceOffer(): BelongsTo
    {
        return $this->belongsTo(self::class, 'source_offer_id');
    }

    public function copies(): HasMany
    {
        return $this->hasMany(self::class, 'source_offer_id');
    }

    public function isCopy(): bool
    {
        return $this->source_offer_id !== null;
    }

    public function scopeRunning($query, $at = null)
    {
        $at = $at ?: now();

        return $query->where(fn ($q) => $q->whereNull('starts_at')->orWhere('starts_at', '<=', $at))
            ->where(fn ($q) => $q->whereNull('ends_at')->orWhere('ends_at', '>=', $at));
    }

    public function isRunning($at = null): bool
    {
        $at = $at ?: now();

        return (! $this->starts_at || $this->starts_at->lte($at))
            && (! $this->ends_at || $this->ends_at->gte($at));
    }

    public function label(): string
    {
        return $this->discount_type === 'percentage'
            ? rtrim(rtrim((string) $this->value, '0'), '.').'% off'
            : '₹'.number_format((float) $this->value, 2).' off';
    }

    public function discountFor(float $price): float
    {
        $discount = $this->discount_type === 'percentage'
            ? $price * ((float) $this->value / 100)
            : (float) $this->value;

        return round(min($discount, $price), 2);
    }

    public function applyTo(float $price): float
    {
        return round($price - $this->discountFor($price), 2);
    }
}
