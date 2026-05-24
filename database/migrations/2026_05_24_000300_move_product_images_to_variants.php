<?php

use App\Models\ProductVariant;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('product_images')) {
            return;
        }

        if (Schema::hasColumn('product_images', 'product_id')) {
            DB::table('product_images')
                ->whereNull('product_variant_id')
                ->orderBy('id')
                ->each(function (object $image) {
                    $variantId = ProductVariant::query()
                        ->where('product_id', $image->product_id)
                        ->orderBy('id')
                        ->value('id');

                    if ($variantId) {
                        DB::table('product_images')->where('id', $image->id)->update(['product_variant_id' => $variantId]);
                    } else {
                        DB::table('product_images')->where('id', $image->id)->delete();
                    }
                });

            if (DB::getDriverName() === 'mysql') {
                Schema::table('product_images', function (Blueprint $table) {
                    $table->dropForeign(['product_id']);
                    $table->dropForeign(['product_variant_id']);
                    $table->dropColumn('product_id');
                });

                DB::statement('ALTER TABLE product_images MODIFY product_variant_id BIGINT UNSIGNED NOT NULL');

                Schema::table('product_images', function (Blueprint $table) {
                    $table->foreign('product_variant_id')->references('id')->on('product_variants')->cascadeOnUpdate()->cascadeOnDelete();
                    $table->index(['product_variant_id', 'sort_order']);
                });
            }
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('product_images') || Schema::hasColumn('product_images', 'product_id')) {
            return;
        }

        if (DB::getDriverName() === 'mysql') {
            Schema::table('product_images', function (Blueprint $table) {
                $table->dropForeign(['product_variant_id']);
                $table->foreignId('product_id')->nullable()->after('id')->constrained()->cascadeOnUpdate()->cascadeOnDelete();
            });

            DB::table('product_images')
                ->join('product_variants', 'product_images.product_variant_id', '=', 'product_variants.id')
                ->update(['product_images.product_id' => DB::raw('product_variants.product_id')]);

            DB::statement('ALTER TABLE product_images MODIFY product_id BIGINT UNSIGNED NOT NULL');

            Schema::table('product_images', function (Blueprint $table) {
                $table->foreign('product_variant_id')->references('id')->on('product_variants')->cascadeOnUpdate()->nullOnDelete();
            });
        }
    }
};
