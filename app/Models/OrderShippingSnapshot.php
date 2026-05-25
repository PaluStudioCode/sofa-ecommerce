<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderShippingSnapshot extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'origin_latitude' => 'decimal:8',
            'origin_longitude' => 'decimal:8',
            'shipping_cost_per_km' => 'decimal:2',
            'distance_km' => 'decimal:2',
            'billable_distance_km' => 'decimal:2',
            'shipping_cost' => 'decimal:2',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function shippingSetting(): BelongsTo
    {
        return $this->belongsTo(ShippingSetting::class);
    }
}
