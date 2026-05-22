<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('stores')) {
            Schema::create('stores', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->text('description')->nullable();
                $table->decimal('latitude', 10, 8);
                $table->decimal('longitude', 11, 8);
                $table->decimal('radius_km', 8, 2)->unsigned();
                $table->decimal('shipping_cost', 15, 2)->unsigned()->default(0);
                $table->unsignedInteger('priority')->default(0);
                $table->boolean('is_active')->default(true)->index();
                $table->timestamps();
                $table->softDeletes();

                $table->index(['is_active', 'priority']);
                $table->index(['latitude', 'longitude']);
            });
        }

        if (Schema::hasTable('shipping_areas')) {
            DB::table('shipping_areas')
                ->orderBy('id')
                ->get()
                ->each(function ($area) {
                    DB::table('stores')->updateOrInsert([
                        'id' => $area->id,
                    ], [
                        'name' => $area->name,
                        'description' => $area->description,
                        'latitude' => $area->center_latitude,
                        'longitude' => $area->center_longitude,
                        'radius_km' => $area->radius_km,
                        'shipping_cost' => $area->shipping_cost,
                        'priority' => $area->priority,
                        'is_active' => $area->is_active,
                        'created_at' => $area->created_at,
                        'updated_at' => $area->updated_at,
                        'deleted_at' => $area->deleted_at,
                    ]);
                });
        }

        if (Schema::hasTable('orders') && ! Schema::hasColumn('orders', 'store_id')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->foreignId('store_id')
                    ->nullable()
                    ->after(Schema::hasColumn('orders', 'shipping_area_id') ? 'shipping_area_id' : 'voucher_id')
                    ->constrained()
                    ->cascadeOnUpdate()
                    ->nullOnDelete();
            });
        }

        if (
            Schema::hasTable('orders')
            && Schema::hasColumn('orders', 'store_id')
            && Schema::hasColumn('orders', 'shipping_area_id')
        ) {
            DB::table('orders')
                ->whereNull('store_id')
                ->whereNotNull('shipping_area_id')
                ->update(['store_id' => DB::raw('shipping_area_id')]);
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('orders') && Schema::hasColumn('orders', 'store_id')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->dropConstrainedForeignId('store_id');
            });
        }

        Schema::dropIfExists('stores');
    }
};
