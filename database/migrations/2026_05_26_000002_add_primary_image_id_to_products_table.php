<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('products') || Schema::hasColumn('products', 'primary_image_id')) {
            return;
        }

        Schema::table('products', function (Blueprint $table) {
            $table->foreignId('primary_image_id')
                ->nullable()
                ->after('is_featured')
                ->constrained('product_images')
                ->cascadeOnUpdate()
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('products') || ! Schema::hasColumn('products', 'primary_image_id')) {
            return;
        }

        Schema::table('products', function (Blueprint $table) {
            $table->dropConstrainedForeignId('primary_image_id');
        });
    }
};
