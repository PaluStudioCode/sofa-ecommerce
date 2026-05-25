<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderShippingSnapshot extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [];
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
