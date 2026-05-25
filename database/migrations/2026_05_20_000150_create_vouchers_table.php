<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('vouchers')) {
            return;
        }

        Schema::create('vouchers', function (Blueprint $table) {
            $table->id();
            $table->string('code', 100)->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->enum('discount_type', ['nominal', 'percentage']);
            $table->decimal('discount_value', 15, 2)->unsigned();
            $table->decimal('max_discount', 15, 2)->unsigned()->nullable();
            $table->decimal('minimum_purchase', 15, 2)->unsigned()->default(0);
            $table->unsignedInteger('quota')->nullable();
            $table->unsignedInteger('per_user_limit')->nullable();
            $table->dateTime('start_at');
            $table->dateTime('end_at');
            $table->enum('status', ['aktif', 'nonaktif', 'kedaluwarsa', 'kuota_habis'])->default('aktif')->index();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'start_at', 'end_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vouchers');
    }
};
