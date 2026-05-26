<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('order_shipping_snapshots')) {
            return;
        }

        Schema::table('order_shipping_snapshots', function (Blueprint $table) {
            if (! Schema::hasColumn('order_shipping_snapshots', 'origin_address')) {
                $table->text('origin_address')->nullable()->after('origin_name');
            }

            if (! Schema::hasColumn('order_shipping_snapshots', 'origin_latitude')) {
                $table->decimal('origin_latitude', 10, 8)->nullable()->after('origin_address');
            }

            if (! Schema::hasColumn('order_shipping_snapshots', 'origin_longitude')) {
                $table->decimal('origin_longitude', 11, 8)->nullable()->after('origin_latitude');
            }

            if (! Schema::hasColumn('order_shipping_snapshots', 'shipping_cost_per_km')) {
                $table->decimal('shipping_cost_per_km', 15, 2)->unsigned()->nullable()->after('origin_longitude');
            }

            if (! Schema::hasColumn('order_shipping_snapshots', 'distance_km')) {
                $table->decimal('distance_km', 8, 2)->unsigned()->nullable()->after('shipping_cost_per_km');
            }

            if (! Schema::hasColumn('order_shipping_snapshots', 'billable_distance_km')) {
                $table->decimal('billable_distance_km', 8, 2)->unsigned()->nullable()->after('distance_km');
            }

            if (! Schema::hasColumn('order_shipping_snapshots', 'shipping_cost')) {
                $table->decimal('shipping_cost', 15, 2)->unsigned()->nullable()->after('billable_distance_km');
            }

            if (! Schema::hasColumn('order_shipping_snapshots', 'duration_seconds')) {
                $table->unsignedInteger('duration_seconds')->nullable()->after('shipping_cost');
            }

            if (! Schema::hasColumn('order_shipping_snapshots', 'distance_provider')) {
                $table->string('distance_provider', 50)->nullable()->after('duration_seconds');
            }

            if (! Schema::hasColumn('order_shipping_snapshots', 'route_geometry')) {
                $table->json('route_geometry')->nullable()->after('distance_provider');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('order_shipping_snapshots')) {
            return;
        }

        $columns = array_values(array_filter([
            'origin_address',
            'origin_latitude',
            'origin_longitude',
            'shipping_cost_per_km',
            'distance_km',
            'billable_distance_km',
            'shipping_cost',
            'duration_seconds',
            'distance_provider',
            'route_geometry',
        ], fn (string $column) => Schema::hasColumn('order_shipping_snapshots', $column)));

        if ($columns === []) {
            return;
        }

        Schema::table('order_shipping_snapshots', function (Blueprint $table) use ($columns) {
            $table->dropColumn($columns);
        });
    }
};
