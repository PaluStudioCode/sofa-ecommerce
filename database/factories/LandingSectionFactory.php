<?php

namespace Database\Factories;

use App\Models\LandingSection;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<LandingSection> */
class LandingSectionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'section_key' => fake()->randomElement(['hero', 'value', 'promo', 'featured_products']),
            'title' => fake()->sentence(3),
            'subtitle' => fake()->optional()->sentence(6),
            'content' => fake()->optional()->paragraph(),
            'image_path' => fake()->optional()->passthrough('landing/demo-sofa.jpg'),
            'button_label' => fake()->optional()->passthrough('Lihat Katalog'),
            'button_url' => fake()->optional()->passthrough('/catalog'),
            'sort_order' => fake()->numberBetween(0, 10),
            'is_active' => true,
        ];
    }
}
