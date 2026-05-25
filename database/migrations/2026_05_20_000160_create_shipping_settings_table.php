<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('shipping_settings')) {
            return;
        }

        Schema::create('shipping_settings', function (Blueprint $table) {
            $table->id();
            $table->string('origin_name');
            $table->text('origin_address')->nullable();
            $table->decimal('origin_latitude', 10, 8);
            $table->decimal('origin_longitude', 11, 8);
            $table->decimal('radius_km', 8, 2)->unsigned();
            $table->decimal('shipping_cost_per_km', 15, 2)->unsigned();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();

            $table->index(['origin_latitude', 'origin_longitude']);
        });

        DB::statement('ALTER TABLE shipping_settings ADD CONSTRAINT chk_shipping_settings_radius_positive CHECK (radius_km > 0)');
    }

    public function down(): void
    {
        Schema::dropIfExists('shipping_settings');
    }
};
