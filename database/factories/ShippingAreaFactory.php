<?php

namespace Database\Factories;

use App\Models\ShippingArea;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<ShippingArea> */
class ShippingAreaFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => 'Area '.fake()->city(),
            'description' => fake()->optional()->sentence(),
            'center_latitude' => fake()->latitude(-6.4, -6.1),
            'center_longitude' => fake()->longitude(106.6, 107.0),
            'radius_km' => fake()->randomFloat(2, 3, 20),
            'shipping_cost' => fake()->numberBetween(5, 25) * 10000,
            'priority' => fake()->numberBetween(0, 10),
            'is_active' => true,
        ];
    }
}
