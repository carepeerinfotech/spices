<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['code', 'discount_type', 'value', 'is_active', 'starts_at', 'ends_at'])]
class Coupon extends Model
{
    protected function casts(): array
    {
        return [
            'value' => 'decimal:2',
            'is_active' => 'boolean',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (self $coupon) {
            $coupon->code = strtoupper(trim((string) $coupon->code));
        });
    }

    public function isRunning($at = null): bool
    {
        $at = $at ?: now();

        return $this->is_active
            && (! $this->starts_at || $this->starts_at->lte($at))
            && (! $this->ends_at || $this->ends_at->gte($at));
    }

    public function label(): string
    {
        return $this->discount_type === 'percentage'
            ? rtrim(rtrim((string) $this->value, '0'), '.').'% off'
            : '₹'.number_format((float) $this->value, 2).' off';
    }

    public function discountFor(float $amount): float
    {
        $discount = $this->discount_type === 'percentage'
            ? $amount * ((float) $this->value / 100)
            : (float) $this->value;

        return round(min($discount, max($amount, 0)), 2);
    }
}
