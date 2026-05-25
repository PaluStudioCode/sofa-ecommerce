<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('order_addresses')) {
            return;
        }

        Schema::create('order_addresses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->unique()->constrained()->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('user_address_id')->nullable()->constrained()->cascadeOnUpdate()->nullOnDelete();
            $table->string('recipient_name');
            $table->string('phone', 30);
            $table->text('detail');
            $table->text('formatted_address');
            $table->string('city')->nullable();
            $table->string('district')->nullable();
            $table->string('postal_code', 20)->nullable();
            $table->decimal('latitude', 10, 8);
            $table->decimal('longitude', 11, 8);
            $table->timestamps();

            $table->index(['latitude', 'longitude']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_addresses');
    }
};
