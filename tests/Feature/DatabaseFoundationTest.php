<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class DatabaseFoundationTest extends TestCase
{
    use RefreshDatabase;

    public function test_core_tables_and_key_columns_exist(): void
    {
        foreach ([
            'users',
            'password_reset_tokens',
            'user_addresses',
            'categories',
            'products',
            'product_variants',
            'product_images',
            'cart_items',
            'vouchers',
            'shipping_settings',
            'orders',
            'order_totals',
            'order_deliveries',
            'order_addresses',
            'order_voucher_snapshots',
            'order_shipping_snapshots',
            'order_items',
            'payments',
            'system_settings',
        ] as $table) {
            $this->assertTrue(Schema::hasTable($table), "Missing table [{$table}].");
        }

        $this->assertTrue(Schema::hasColumns('users', ['phone', 'role', 'deleted_at']));
        $this->assertTrue(Schema::hasColumns('user_addresses', ['recipient_name', 'phone', 'formatted_address', 'latitude', 'longitude', 'is_default']));
        $this->assertTrue(Schema::hasColumns('product_variants', ['sku', 'price', 'stock', 'reserved_stock', 'status']));
        $this->assertTrue(Schema::hasColumns('order_totals', ['subtotal_amount', 'discount_amount', 'shipping_cost', 'total_amount']));
        $this->assertTrue(Schema::hasColumns('order_addresses', ['recipient_name', 'phone', 'formatted_address', 'latitude', 'longitude']));
        $this->assertTrue(Schema::hasColumns('order_voucher_snapshots', ['voucher_id', 'voucher_code', 'voucher_name']));
        $this->assertFalse(Schema::hasColumn('order_voucher_snapshots', 'discount_type'));
        $this->assertTrue(Schema::hasColumns('order_shipping_snapshots', ['shipping_setting_id', 'origin_name']));
        $this->assertFalse(Schema::hasColumn('order_shipping_snapshots', 'distance_km'));
        $this->assertTrue(Schema::hasColumns('payments', ['attempt_number', 'midtrans_order_id', 'snap_token', 'raw_response']));
    }
}
