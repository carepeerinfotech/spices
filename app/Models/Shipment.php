<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'order_id', 'provider', 'provider_order_id', 'shipment_id', 'awb_code',
    'courier_id', 'courier_name', 'status', 'freight_charge', 'etd_days',
    'label_url', 'invoice_url', 'manifest_url', 'request_payload',
    'response_payload', 'tracking_data', 'last_error', 'picked_up_at', 'delivered_at',
])]
class Shipment extends Model
{
    protected function casts(): array
    {
        return [
            'freight_charge' => 'decimal:2',
            'etd_days' => 'integer',
            'request_payload' => 'array',
            'response_payload' => 'array',
            'tracking_data' => 'array',
            'picked_up_at' => 'datetime',
            'delivered_at' => 'datetime',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function logs(): HasMany
    {
        return $this->hasMany(ShipmentLog::class);
    }
}
