<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('user_addresses')) {
            return;
        }

        Schema::create('user_addresses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnUpdate()->cascadeOnDelete();
            $table->string('recipient_name');
            $table->string('phone', 30);
            $table->text('detail');
            $table->text('formatted_address');
            $table->string('city')->nullable();
            $table->string('district')->nullable();
            $table->string('postal_code', 20)->nullable();
            $table->decimal('latitude', 10, 8);
            $table->decimal('longitude', 11, 8);
            $table->boolean('is_default')->default(true)->index();
            $table->timestamps();

            $table->index(['user_id', 'is_default']);
            $table->index(['latitude', 'longitude']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_addresses');
    }
};
