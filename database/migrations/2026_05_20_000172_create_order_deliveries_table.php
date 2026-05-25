<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('order_deliveries')) {
            return;
        }

        Schema::create('order_deliveries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->unique()->constrained()->cascadeOnUpdate()->cascadeOnDelete();
            $table->date('delivery_scheduled_at')->nullable();
            $table->date('delivery_delivered_at')->nullable();
            $table->string('driver_name')->nullable();
            $table->string('driver_phone', 30)->nullable();
            $table->string('vehicle_note')->nullable();
            $table->text('delivery_note')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_deliveries');
    }
};
