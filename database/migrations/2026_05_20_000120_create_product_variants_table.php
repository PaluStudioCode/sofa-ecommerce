<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('product_variants')) {
            return;
        }

        Schema::create('product_variants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnUpdate()->restrictOnDelete();
            $table->string('sku', 100)->nullable()->unique();
            $table->string('variant_name')->nullable();
            $table->string('size')->nullable();
            $table->string('material')->nullable();
            $table->string('color', 100)->nullable();
            $table->decimal('price', 15, 2)->unsigned();
            $table->unsignedInteger('stock')->default(0);
            $table->unsignedInteger('reserved_stock')->default(0);
            $table->enum('status', ['aktif', 'nonaktif', 'stok_habis'])->default('aktif')->index();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['product_id', 'status']);
        });

        DB::statement('ALTER TABLE product_variants ADD CONSTRAINT chk_product_variants_reserved_stock CHECK (reserved_stock <= stock)');
    }

    public function down(): void
    {
        Schema::dropIfExists('product_variants');
    }
};
