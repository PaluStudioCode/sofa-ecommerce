<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('order_totals')) {
            return;
        }

        Schema::create('order_totals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->unique()->constrained()->cascadeOnUpdate()->cascadeOnDelete();
            $table->decimal('subtotal_amount', 15, 2)->unsigned();
            $table->decimal('discount_amount', 15, 2)->unsigned()->default(0);
            $table->decimal('shipping_cost', 15, 2)->unsigned();
            $table->decimal('total_amount', 15, 2)->unsigned();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_totals');
    }
};
