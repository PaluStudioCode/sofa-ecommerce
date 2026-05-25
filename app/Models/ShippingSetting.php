<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ShippingSetting extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'origin_latitude' => 'decimal:8',
            'origin_longitude' => 'decimal:8',
            'radius_km' => 'decimal:2',
            'shipping_cost_per_km' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function shippingSnapshots(): HasMany
    {
        return $this->hasMany(OrderShippingSnapshot::class);
    }

    public function orders(): BelongsToMany
    {
        return $this->belongsToMany(Order::class, 'order_shipping_snapshots')
            ->withTimestamps();
    }
}
