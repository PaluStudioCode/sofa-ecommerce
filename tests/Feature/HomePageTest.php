<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class HomePageTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_open_homepage_with_featured_products_and_active_voucher(): void
    {
        $now = now();

        $categoryId = DB::table('categories')->insertGetId([
            'name' => 'Sofa Ruang Tamu',
            'slug' => 'sofa-ruang-tamu',
            'description' => null,
            'is_active' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $productId = DB::table('products')->insertGetId([
            'category_id' => $categoryId,
            'name' => 'Sofa Unggulan',
            'slug' => 'sofa-unggulan',
            'description' => 'Sofa aktif untuk homepage.',
            'status' => 'aktif',
            'is_featured' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $variantId = DB::table('product_variants')->insertGetId([
            'product_id' => $productId,
            'sku' => 'HOME-001',
            'variant_name' => 'Premium',
            'price' => 3500000,
            'stock' => 4,
            'reserved_stock' => 0,
            'status' => 'aktif',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('product_images')->insert([
            'product_variant_id' => $variantId,
            'file_path' => 'products/featured.jpg',
            'alt_text' => 'Sofa unggulan',
            'sort_order' => 0,
            'is_primary' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('vouchers')->insert([
            'code' => 'SOFAHEMAT',
            'name' => 'Sofa Hemat',
            'description' => 'Diskon homepage.',
            'discount_type' => 'nominal',
            'discount_value' => 250000,
            'max_discount' => null,
            'minimum_purchase' => 1000000,
            'quota' => 10,
            'per_user_limit' => 1,
            'start_at' => $now->copy()->subDay(),
            'end_at' => $now->copy()->addDay(),
            'status' => 'aktif',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $this->get('/')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Home')
                ->has('sections', 0)
                ->has('featuredProducts', 1)
                ->where('featuredProducts.0.name', 'Sofa Unggulan')
                ->where('activeVoucher.code', 'SOFAHEMAT')
            );
    }
}
