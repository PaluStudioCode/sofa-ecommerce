<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('payments')) {
            return;
        }

        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnUpdate()->cascadeOnDelete();
            $table->unsignedInteger('attempt_number');
            $table->string('midtrans_order_id', 100)->unique();
            $table->string('midtrans_transaction_id', 100)->nullable()->index();
            $table->string('payment_type', 100)->nullable();
            $table->enum('status', ['pending', 'success', 'failed', 'expired', 'cancelled'])->default('pending')->index();
            $table->string('transaction_status', 100)->default('pending')->index();
            $table->string('fraud_status', 100)->nullable();
            $table->decimal('gross_amount', 15, 2)->unsigned();
            $table->string('snap_token')->nullable();
            $table->string('redirect_url')->nullable();
            $table->dateTime('paid_at')->nullable();
            $table->dateTime('expired_at')->nullable();
            $table->json('raw_response')->nullable();
            $table->timestamps();

            $table->unique(['order_id', 'attempt_number']);
            $table->index(['order_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
