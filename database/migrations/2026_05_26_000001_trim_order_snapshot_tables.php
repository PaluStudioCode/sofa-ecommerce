<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('order_voucher_snapshots')) {
            $columns = array_values(array_filter([
                'discount_type',
                'discount_value',
                'max_discount',
                'minimum_purchase',
            ], fn (string $column) => Schema::hasColumn('order_voucher_snapshots', $column)));

            if ($columns !== []) {
                Schema::table('order_voucher_snapshots', function (Blueprint $table) use ($columns) {
                    $table->dropColumn($columns);
                });
            }
        }

        if (Schema::hasTable('order_shipping_snapshots')) {
            if (Schema::hasColumn('order_shipping_snapshots', 'origin_latitude')
                && Schema::hasColumn('order_shipping_snapshots', 'origin_longitude')) {
                Schema::table('order_shipping_snapshots', function (Blueprint $table) {
                    $table->dropIndex(['origin_latitude', 'origin_longitude']);
                });
            }

            $columns = array_values(array_filter([
                'origin_address',
                'origin_latitude',
                'origin_longitude',
                'shipping_cost_per_km',
                'distance_km',
                'billable_distance_km',
                'shipping_cost',
            ], fn (string $column) => Schema::hasColumn('order_shipping_snapshots', $column)));

            if ($columns !== []) {
                Schema::table('order_shipping_snapshots', function (Blueprint $table) use ($columns) {
                    $table->dropColumn($columns);
                });
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('order_voucher_snapshots')
            && ! Schema::hasColumn('order_voucher_snapshots', 'discount_type')) {
            Schema::table('order_voucher_snapshots', function (Blueprint $table) {
                $table->enum('discount_type', ['nominal', 'percentage'])->nullable()->after('voucher_name');
                $table->decimal('discount_value', 15, 2)->unsigned()->nullable()->after('discount_type');
                $table->decimal('max_discount', 15, 2)->unsigned()->nullable()->after('discount_value');
                $table->decimal('minimum_purchase', 15, 2)->unsigned()->default(0)->after('max_discount');
            });
        }

        if (Schema::hasTable('order_shipping_snapshots')
            && ! Schema::hasColumn('order_shipping_snapshots', 'origin_address')) {
            Schema::table('order_shipping_snapshots', function (Blueprint $table) {
                $table->text('origin_address')->nullable()->after('origin_name');
                $table->decimal('origin_latitude', 10, 8)->nullable()->after('origin_address');
                $table->decimal('origin_longitude', 11, 8)->nullable()->after('origin_latitude');
                $table->decimal('shipping_cost_per_km', 15, 2)->unsigned()->nullable()->after('origin_longitude');
                $table->decimal('distance_km', 8, 2)->unsigned()->nullable()->after('shipping_cost_per_km');
                $table->decimal('billable_distance_km', 8, 2)->unsigned()->nullable()->after('distance_km');
                $table->decimal('shipping_cost', 15, 2)->unsigned()->nullable()->after('billable_distance_km');
                $table->index(['origin_latitude', 'origin_longitude']);
            });
        }
    }
};
