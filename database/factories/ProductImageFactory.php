<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<ProductImage> */
class ProductImageFactory extends Factory
{
    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'product_variant_id' => null,
            'file_path' => 'products/demo-sofa-'.fake()->unique()->numberBetween(1, 999).'.jpg',
            'alt_text' => fake()->sentence(3),
            'sort_order' => 0,
            'is_primary' => true,
        ];
    }
}
