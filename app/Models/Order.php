<?php

namespace App\Models;

use Database\Factories\OrderFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;
use Illuminate\Database\Eloquent\SoftDeletes;

class Order extends Model
{
    /** @use HasFactory<OrderFactory> */
    use HasFactory, SoftDeletes;

    protected $guarded = [];

    protected function casts(): array
    {
        return [];
    }

    protected function paymentStatus(): Attribute
    {
        return Attribute::get(fn (): string => $this->computedPaymentStatus());
    }

    protected function subtotalAmount(): Attribute
    {
        return Attribute::get(fn (): mixed => $this->relatedValue('total', 'subtotal_amount'));
    }

    protected function discountAmount(): Attribute
    {
        return Attribute::get(fn (): mixed => $this->relatedValue('total', 'discount_amount'));
    }

    protected function shippingCost(): Attribute
    {
        return Attribute::get(fn (): mixed => $this->relatedValue('total', 'shipping_cost'));
    }

    protected function totalAmount(): Attribute
    {
        return Attribute::get(fn (): mixed => $this->relatedValue('total', 'total_amount'));
    }

    protected function deliveryScheduledAt(): Attribute
    {
        return Attribute::get(fn (): mixed => $this->relatedValue('delivery', 'delivery_scheduled_at'));
    }

    protected function deliveryDeliveredAt(): Attribute
    {
        return Attribute::get(fn (): mixed => $this->relatedValue('delivery', 'delivery_delivered_at'));
    }

    protected function driverName(): Attribute
    {
        return Attribute::get(fn (): mixed => $this->relatedValue('delivery', 'driver_name'));
    }

    protected function driverPhone(): Attribute
    {
        return Attribute::get(fn (): mixed => $this->relatedValue('delivery', 'driver_phone'));
    }

    protected function vehicleNote(): Attribute
    {
        return Attribute::get(fn (): mixed => $this->relatedValue('delivery', 'vehicle_note'));
    }

    protected function deliveryNote(): Attribute
    {
        return Attribute::get(fn (): mixed => $this->relatedValue('delivery', 'delivery_note'));
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function total(): HasOne
    {
        return $this->hasOne(OrderTotal::class);
    }

    public function delivery(): HasOne
    {
        return $this->hasOne(OrderDelivery::class);
    }

    public function address(): HasOne
    {
        return $this->hasOne(OrderAddress::class);
    }

    public function voucherSnapshot(): HasOne
    {
        return $this->hasOne(OrderVoucherSnapshot::class);
    }

    public function shippingSnapshot(): HasOne
    {
        return $this->hasOne(OrderShippingSnapshot::class);
    }

    public function userAddress(): HasOneThrough
    {
        return $this->hasOneThrough(UserAddress::class, OrderAddress::class, 'order_id', 'id', 'id', 'user_address_id');
    }

    public function voucher(): HasOneThrough
    {
        return $this->hasOneThrough(Voucher::class, OrderVoucherSnapshot::class, 'order_id', 'id', 'id', 'voucher_id');
    }

    public function shippingSetting(): HasOneThrough
    {
        return $this->hasOneThrough(ShippingSetting::class, OrderShippingSnapshot::class, 'order_id', 'id', 'id', 'shipping_setting_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function computedPaymentStatus(): string
    {
        if ($this->relationLoaded('payments')) {
            if ($this->payments->contains('status', 'success')) {
                return 'success';
            }

            return $this->payments
                ->sortByDesc('attempt_number')
                ->first()?->status ?? 'pending';
        }

        if ($this->payments()->where('status', 'success')->exists()) {
            return 'success';
        }

        return $this->payments()->latest('attempt_number')->value('status') ?? 'pending';
    }

    private function relatedValue(string $relation, string $column): mixed
    {
        $model = $this->relationLoaded($relation)
            ? $this->getRelation($relation)
            : $this->{$relation}()->first();

        return $model?->{$column};
    }
}
