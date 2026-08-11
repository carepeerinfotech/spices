<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['cart_id', 'product_id', 'product_variant_id', 'quantity', 'price'])]
class CartItem extends Model
{
    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'price' => 'decimal:2',
        ];
    }

    public function cart(): BelongsTo
    {
        return $this->belongsTo(Cart::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    public function offer(): ?ProductOffer
    {
        return $this->product?->activeOffer();
    }

    /** Discount taken off a single unit by the running offer. */
    public function unitDiscount(): float
    {
        return $this->offer()?->discountFor((float) $this->price) ?? 0.0;
    }

    public function discountedPrice(): float
    {
        return round((float) $this->price - $this->unitDiscount(), 2);
    }

    public function lineDiscount(): float
    {
        return round($this->unitDiscount() * $this->quantity, 2);
    }

    /** What the line is charged at, after any running offer. */
    public function lineTotal(): float
    {
        return round($this->discountedPrice() * $this->quantity, 2);
    }

    /** What the line would cost without the offer. */
    public function lineSubtotal(): float
    {
        return round((float) $this->price * $this->quantity, 2);
    }
}
