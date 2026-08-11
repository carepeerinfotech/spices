<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable([
    'order_number', 'user_id', 'customer_name', 'customer_email', 'customer_phone',
    'billing_name', 'billing_email', 'billing_phone', 'billing_address',
    'billing_city', 'billing_state', 'billing_postal_code', 'billing_country',
    'billing_same_as_shipping', 'shipping_name',
    'shipping_address', 'shipping_city', 'shipping_state', 'shipping_postal_code',
    'shipping_country', 'subtotal', 'shipping_amount', 'tax_amount', 'tax_percent',
    'total', 'currency', 'payment_method', 'payment_status', 'status', 'notes',
    'estimated_delivery_days', 'courier_name', 'shipping_weight',
])]
class Order extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'subtotal' => 'decimal:2',
            'shipping_amount' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'tax_percent' => 'decimal:2',
            'total' => 'decimal:2',
            'shipping_weight' => 'decimal:3',
            'billing_same_as_shipping' => 'boolean',
            'estimated_delivery_days' => 'integer',
        ];
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function offers(): HasMany
    {
        return $this->hasMany(OrderOffer::class);
    }

    public function offersDiscount(): float
    {
        return (float) $this->offers->sum('discount_amount');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(PaymentTransaction::class);
    }

    public function shipment(): HasOne
    {
        return $this->hasOne(Shipment::class)->latestOfMany();
    }

    public function shipments(): HasMany
    {
        return $this->hasMany(Shipment::class);
    }

    public function isPaid(): bool
    {
        return $this->payment_status === 'paid' || $this->payment_method === 'cod';
    }
}
