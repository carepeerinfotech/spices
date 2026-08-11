<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'order_id', 'order_item_id', 'product_id', 'product_offer_id', 'name',
    'discount_type', 'value', 'unit_discount', 'discount_amount', 'starts_at', 'ends_at',
])]
class OrderOffer extends Model
{
    protected function casts(): array
    {
        return [
            'value' => 'decimal:2',
            'unit_discount' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function productOffer(): BelongsTo
    {
        return $this->belongsTo(ProductOffer::class);
    }

    public function label(): string
    {
        return $this->name ?: ($this->discount_type === 'percentage'
            ? rtrim(rtrim((string) $this->value, '0'), '.').'% off'
            : '₹'.number_format((float) $this->value, 2).' off');
    }

    /**
     * Snapshot the offer that was running against an order line at the moment
     * the order was placed. $unitDiscount is what was actually taken off one
     * unit, since the order item already stores the discounted price.
     */
    public static function captureFor(OrderItem $item, ProductOffer $offer, ?float $unitDiscount = null): self
    {
        $unitDiscount ??= $offer->discountFor((float) $item->price);

        return static::create([
            'order_id' => $item->order_id,
            'order_item_id' => $item->id,
            'product_id' => $item->product_id,
            'product_offer_id' => $offer->id,
            'name' => $offer->name,
            'discount_type' => $offer->discount_type,
            'value' => $offer->value,
            'unit_discount' => $unitDiscount,
            'discount_amount' => round($unitDiscount * $item->quantity, 2),
            'starts_at' => $offer->starts_at,
            'ends_at' => $offer->ends_at,
        ]);
    }
}
