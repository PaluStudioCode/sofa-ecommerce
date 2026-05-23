<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->text('shipping_address')->nullable();
            $table->string('shipping_city')->nullable();
            $table->string('shipping_district')->nullable();
            $table->string('shipping_postal_code', 20)->nullable();
            $table->decimal('shipping_latitude', 10, 8)->nullable();
            $table->decimal('shipping_longitude', 11, 8)->nullable();

            $table->index(['shipping_latitude', 'shipping_longitude']);
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['shipping_latitude', 'shipping_longitude']);
            $table->dropColumn([
                'shipping_address',
                'shipping_city',
                'shipping_district',
                'shipping_postal_code',
                'shipping_latitude',
                'shipping_longitude',
            ]);
        });
    }
};
