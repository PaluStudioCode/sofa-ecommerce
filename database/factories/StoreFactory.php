<?php

namespace Database\Factories;

use App\Models\Store;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Store> */
class StoreFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => 'Toko '.fake()->city(),
            'description' => fake()->optional()->sentence(),
            'latitude' => fake()->latitude(-6.4, -6.1),
            'longitude' => fake()->longitude(106.6, 107.0),
            'radius_km' => fake()->randomFloat(2, 3, 20),
            'shipping_cost' => fake()->numberBetween(1, 5) * 10000,
            'priority' => 0,
            'is_active' => true,
        ];
    }
}
