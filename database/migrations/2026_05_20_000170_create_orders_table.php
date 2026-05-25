<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('orders')) {
            return;
        }

        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number', 100)->unique();
            $table->foreignId('user_id')->constrained()->cascadeOnUpdate()->restrictOnDelete();
            $table->text('customer_note')->nullable();
            $table->enum('order_status', [
                'menunggu_pembayaran',
                'diproses',
                'dalam_perjalanan',
                'barang_diterima',
            ])->default('menunggu_pembayaran')->index();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['user_id', 'order_status']);
            $table->index(['order_status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
