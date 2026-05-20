<?php

namespace Database\Factories;

use App\Models\Voucher;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<Voucher> */
class VoucherFactory extends Factory
{
    public function definition(): array
    {
        return [
            'code' => Str::upper(fake()->unique()->bothify('SOFA##??')),
            'name' => fake()->words(3, true),
            'description' => fake()->optional()->sentence(),
            'discount_type' => 'nominal',
            'discount_value' => 100000,
            'max_discount' => null,
            'minimum_purchase' => 1000000,
            'quota' => 100,
            'used_count' => 0,
            'per_user_limit' => 1,
            'start_at' => now()->subDay(),
            'end_at' => now()->addMonth(),
            'status' => 'aktif',
        ];
    }

    public function percentage(): static
    {
        return $this->state(fn () => [
            'discount_type' => 'percentage',
            'discount_value' => 10,
            'max_discount' => 250000,
        ]);
    }

    public function expired(): static
    {
        return $this->state(fn () => [
            'start_at' => now()->subMonths(2),
            'end_at' => now()->subMonth(),
            'status' => 'kedaluwarsa',
        ]);
    }
}
