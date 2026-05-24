<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('phone', 30)->nullable()->after('password');
            $table->enum('role', ['customer', 'admin'])->default('customer')->after('phone')->index();
            $table->softDeletes();
        });

        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained()->cascadeOnUpdate()->restrictOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description');
            $table->enum('status', ['aktif', 'nonaktif'])->default('aktif')->index();
            $table->boolean('is_featured')->default(false)->index();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['category_id', 'status']);
        });

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

        Schema::create('product_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_variant_id')->constrained()->cascadeOnUpdate()->cascadeOnDelete();
            $table->string('file_path');
            $table->string('alt_text')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_primary')->default(false)->index();
            $table->timestamps();

            $table->index(['product_variant_id', 'sort_order']);
        });

        Schema::create('cart_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('product_variant_id')->constrained()->cascadeOnUpdate()->cascadeOnDelete();
            $table->unsignedInteger('quantity');
            $table->timestamps();

            $table->unique(['user_id', 'product_variant_id']);
            $table->index(['user_id', 'product_id']);
        });

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
            $table->unsignedInteger('used_count')->default(0);
            $table->unsignedInteger('per_user_limit')->nullable();
            $table->dateTime('start_at');
            $table->dateTime('end_at');
            $table->enum('status', ['aktif', 'nonaktif', 'kedaluwarsa', 'kuota_habis'])->default('aktif')->index();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'start_at', 'end_at']);
        });

        Schema::create('stores', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->decimal('latitude', 10, 8);
            $table->decimal('longitude', 11, 8);
            $table->decimal('radius_km', 8, 2)->unsigned();
            $table->decimal('shipping_cost', 15, 2)->unsigned();
            $table->unsignedInteger('priority')->default(0);
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['is_active', 'priority']);
            $table->index(['latitude', 'longitude']);
        });

        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number', 100)->unique();
            $table->foreignId('user_id')->constrained()->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignId('voucher_id')->nullable()->constrained()->cascadeOnUpdate()->nullOnDelete();
            $table->foreignId('store_id')->nullable()->constrained()->cascadeOnUpdate()->nullOnDelete();
            $table->string('customer_name');
            $table->string('customer_phone', 30);
            $table->text('shipping_address');
            $table->string('shipping_city')->nullable();
            $table->string('shipping_district')->nullable();
            $table->string('shipping_postal_code', 20)->nullable();
            $table->decimal('shipping_latitude', 10, 8);
            $table->decimal('shipping_longitude', 11, 8);
            $table->text('shipping_note')->nullable();
            $table->decimal('subtotal_amount', 15, 2)->unsigned();
            $table->decimal('discount_amount', 15, 2)->unsigned()->default(0);
            $table->decimal('shipping_cost', 15, 2)->unsigned();
            $table->decimal('total_amount', 15, 2)->unsigned();
            $table->enum('order_status', [
                'menunggu_pembayaran',
                'dibayar',
                'perlu_review_admin',
                'diproses',
                'dikirim',
                'selesai',
                'dibatalkan',
            ])->default('menunggu_pembayaran')->index();
            $table->enum('payment_status', ['pending', 'success', 'failed', 'expired', 'cancelled'])->default('pending')->index();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['user_id', 'order_status']);
            $table->index(['payment_status', 'created_at']);
        });

        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignId('product_variant_id')->nullable()->constrained()->cascadeOnUpdate()->nullOnDelete();
            $table->string('product_name');
            $table->string('variant_name')->nullable();
            $table->string('variant_sku', 100)->nullable();
            $table->string('variant_size')->nullable();
            $table->string('variant_material')->nullable();
            $table->string('variant_color', 100)->nullable();
            $table->decimal('product_price', 15, 2)->unsigned();
            $table->unsignedInteger('quantity');
            $table->decimal('subtotal', 15, 2)->unsigned();
            $table->timestamps();

            $table->index(['order_id', 'product_id']);
        });

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
            $table->unsignedBigInteger('pending_order_id')->nullable()->virtualAs("case when `status` = 'pending' then `order_id` end");
            $table->unsignedBigInteger('success_order_id')->nullable()->virtualAs("case when `status` = 'success' then `order_id` end");
            $table->timestamps();

            $table->unique(['order_id', 'attempt_number']);
            $table->unique('pending_order_id');
            $table->unique('success_order_id');
            $table->index(['order_id', 'status']);
        });

        Schema::create('shipments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->unique()->constrained()->cascadeOnUpdate()->cascadeOnDelete();
            $table->enum('status', ['belum_dijadwalkan', 'dijadwalkan', 'dalam_pengiriman', 'terkirim', 'gagal_dikirim'])->default('belum_dijadwalkan')->index();
            $table->dateTime('scheduled_at')->nullable();
            $table->dateTime('delivered_at')->nullable();
            $table->string('driver_name')->nullable();
            $table->string('driver_phone', 30)->nullable();
            $table->string('vehicle_note')->nullable();
            $table->text('shipping_note')->nullable();
            $table->timestamps();
        });

        Schema::create('voucher_usages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('voucher_id')->constrained()->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignId('order_id')->unique()->constrained()->cascadeOnUpdate()->cascadeOnDelete();
            $table->decimal('discount_amount', 15, 2)->unsigned();
            $table->dateTime('used_at');
            $table->timestamps();

            $table->unique(['voucher_id', 'user_id', 'order_id']);
            $table->index(['voucher_id', 'user_id']);
        });

        Schema::create('landing_sections', function (Blueprint $table) {
            $table->id();
            $table->string('section_key', 100)->index();
            $table->string('title')->nullable();
            $table->string('subtitle')->nullable();
            $table->text('content')->nullable();
            $table->string('image_path')->nullable();
            $table->string('button_label', 100)->nullable();
            $table->string('button_url')->nullable();
            $table->unsignedInteger('sort_order')->default(0)->index();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });

        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->cascadeOnUpdate()->nullOnDelete();
            $table->foreignId('order_id')->nullable()->constrained()->cascadeOnUpdate()->cascadeOnDelete();
            $table->enum('channel', ['whatsapp'])->default('whatsapp')->index();
            $table->string('recipient');
            $table->text('message');
            $table->enum('status', ['pending', 'sent', 'failed'])->default('pending')->index();
            $table->string('provider', 100)->nullable();
            $table->json('provider_response')->nullable();
            $table->dateTime('sent_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index(['order_id', 'channel']);
        });

        DB::statement('ALTER TABLE product_variants ADD CONSTRAINT chk_product_variants_reserved_stock CHECK (reserved_stock <= stock)');
        DB::statement('ALTER TABLE stores ADD CONSTRAINT chk_stores_radius_positive CHECK (radius_km > 0)');
        DB::statement('ALTER TABLE vouchers ADD CONSTRAINT chk_vouchers_used_count_quota CHECK (quota IS NULL OR used_count <= quota)');
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
        Schema::dropIfExists('landing_sections');
        Schema::dropIfExists('voucher_usages');
        Schema::dropIfExists('shipments');
        Schema::dropIfExists('payments');
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('orders');
        Schema::dropIfExists('stores');
        Schema::dropIfExists('vouchers');
        Schema::dropIfExists('cart_items');
        Schema::dropIfExists('product_images');
        Schema::dropIfExists('product_variants');
        Schema::dropIfExists('products');
        Schema::dropIfExists('categories');

        Schema::table('users', function (Blueprint $table) {
            $table->dropSoftDeletes();
            $table->dropColumn(['phone', 'role']);
        });
    }
};
