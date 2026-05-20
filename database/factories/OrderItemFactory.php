<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<OrderItem> */
class OrderItemFactory extends Factory
{
    public function definition(): array
    {
        $product = Product::factory()->create();
        $variant = ProductVariant::factory()->for($product)->create();
        $quantity = fake()->numberBetween(1, 3);
        $price = (float) $variant->price;

        return [
            'order_id' => Order::factory(),
            'product_id' => $product->id,
            'product_variant_id' => $variant->id,
            'product_name' => $product->name,
            'variant_name' => $variant->variant_name,
            'variant_sku' => $variant->sku,
            'variant_size' => $variant->size,
            'variant_material' => $variant->material,
            'variant_color' => $variant->color,
            'product_price' => $price,
            'quantity' => $quantity,
            'subtotal' => $price * $quantity,
        ];
    }
}
