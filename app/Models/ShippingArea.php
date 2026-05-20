<?php

namespace App\Models;

use Database\Factories\ShippingAreaFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ShippingArea extends Model
{
    /** @use HasFactory<ShippingAreaFactory> */
    use HasFactory, SoftDeletes;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'center_latitude' => 'decimal:8',
            'center_longitude' => 'decimal:8',
            'radius_km' => 'decimal:2',
            'shipping_cost' => 'decimal:2',
            'priority' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }
}
