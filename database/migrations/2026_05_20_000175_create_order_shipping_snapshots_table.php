<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('order_shipping_snapshots')) {
            return;
        }

        Schema::create('order_shipping_snapshots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->unique()->constrained()->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('shipping_setting_id')->nullable()->constrained()->cascadeOnUpdate()->nullOnDelete();
            $table->string('origin_name');
            $table->text('origin_address')->nullable();
            $table->decimal('origin_latitude', 10, 8);
            $table->decimal('origin_longitude', 11, 8);
            $table->decimal('shipping_cost_per_km', 15, 2)->unsigned();
            $table->decimal('distance_km', 8, 2)->unsigned();
            $table->decimal('billable_distance_km', 8, 2)->unsigned();
            $table->decimal('shipping_cost', 15, 2)->unsigned();
            $table->timestamps();

            $table->index(['origin_latitude', 'origin_longitude']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_shipping_snapshots');
    }
};
