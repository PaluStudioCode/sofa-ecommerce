<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<ProductVariant> */
class ProductVariantFactory extends Factory
{
    public function definition(): array
    {
        $stock = fake()->numberBetween(5, 30);

        return [
            'product_id' => Product::factory(),
            'sku' => 'SOFA-'.fake()->unique()->bothify('??###'),
            'variant_name' => fake()->randomElement(['Standar', 'Premium', 'L Section', 'Compact']),
            'size' => fake()->randomElement(['2 Seater', '3 Seater', 'L 240 cm', 'Custom 180 cm']),
            'material' => fake()->randomElement(['Linen', 'Oscar', 'Beludru', 'Kulit Sintetis']),
            'color' => fake()->safeColorName(),
            'price' => fake()->numberBetween(250, 900) * 10000,
            'stock' => $stock,
            'reserved_stock' => fake()->numberBetween(0, min(2, $stock)),
            'status' => 'aktif',
        ];
    }

    public function outOfStock(): static
    {
        return $this->state(fn () => [
            'stock' => 0,
            'reserved_stock' => 0,
            'status' => 'stok_habis',
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['status' => 'nonaktif']);
    }
}
