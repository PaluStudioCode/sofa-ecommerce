<?php

namespace Database\Factories;

use App\Models\CartItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<CartItem> */
class CartItemFactory extends Factory
{
    public function definition(): array
    {
        $product = Product::factory()->create();
        $variant = ProductVariant::factory()->for($product)->create();

        return [
            'user_id' => User::factory(),
            'product_id' => $product->id,
            'product_variant_id' => $variant->id,
            'quantity' => fake()->numberBetween(1, 3),
        ];
    }
}
