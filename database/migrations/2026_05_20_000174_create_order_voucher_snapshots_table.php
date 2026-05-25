<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('order_voucher_snapshots')) {
            return;
        }

        Schema::create('order_voucher_snapshots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->unique()->constrained()->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('voucher_id')->nullable()->constrained()->cascadeOnUpdate()->nullOnDelete();
            $table->string('voucher_code', 100);
            $table->string('voucher_name');
            $table->enum('discount_type', ['nominal', 'percentage']);
            $table->decimal('discount_value', 15, 2)->unsigned();
            $table->decimal('max_discount', 15, 2)->unsigned()->nullable();
            $table->decimal('minimum_purchase', 15, 2)->unsigned()->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_voucher_snapshots');
    }
};
