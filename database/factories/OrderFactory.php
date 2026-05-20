<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\ShippingArea;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Order> */
class OrderFactory extends Factory
{
    public function definition(): array
    {
        $subtotal = fake()->numberBetween(250, 900) * 10000;
        $discount = fake()->numberBetween(0, 10) * 10000;
        $shipping = fake()->numberBetween(5, 20) * 10000;

        return [
            'order_number' => 'ORD-'.now()->format('Ymd').'-'.fake()->unique()->numerify('######'),
            'user_id' => User::factory(),
            'voucher_id' => null,
            'shipping_area_id' => ShippingArea::factory(),
            'customer_name' => fake()->name(),
            'customer_phone' => fake()->phoneNumber(),
            'shipping_address' => fake()->address(),
            'shipping_city' => fake()->city(),
            'shipping_district' => fake()->streetName(),
            'shipping_postal_code' => fake()->postcode(),
            'shipping_latitude' => fake()->latitude(-6.4, -6.1),
            'shipping_longitude' => fake()->longitude(106.6, 107.0),
            'shipping_note' => fake()->optional()->sentence(),
            'subtotal_amount' => $subtotal,
            'discount_amount' => $discount,
            'shipping_cost' => $shipping,
            'total_amount' => $subtotal - $discount + $shipping,
            'order_status' => 'menunggu_pembayaran',
            'payment_status' => 'pending',
        ];
    }

    public function paid(): static
    {
        return $this->state(fn () => [
            'order_status' => 'dibayar',
            'payment_status' => 'success',
        ]);
    }
}
