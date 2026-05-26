<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('products')
            || ! Schema::hasTable('product_variants')
            || ! Schema::hasTable('product_images')
            || ! Schema::hasColumn('products', 'primary_image_id')) {
            return;
        }

        DB::table('products')
            ->whereNull('primary_image_id')
            ->orderBy('id')
            ->select('id')
            ->chunkById(100, function ($products) {
                foreach ($products as $product) {
                    $imageId = $this->firstReadyVariantImageId((int) $product->id)
                        ?? $this->firstProductImageId((int) $product->id);

                    if ($imageId) {
                        DB::table('products')
                            ->where('id', $product->id)
                            ->update(['primary_image_id' => $imageId]);
                    }
                }
            });
    }

    public function down(): void
    {
        //
    }

    private function firstReadyVariantImageId(int $productId): ?int
    {
        $imageId = DB::table('product_images')
            ->join('product_variants', 'product_images.product_variant_id', '=', 'product_variants.id')
            ->where('product_variants.product_id', $productId)
            ->where('product_variants.status', 'aktif')
            ->whereColumn('product_variants.stock', '>', 'product_variants.reserved_stock')
            ->orderByDesc('product_images.is_primary')
            ->orderBy('product_images.sort_order')
            ->orderBy('product_images.id')
            ->value('product_images.id');

        return $imageId === null ? null : (int) $imageId;
    }

    private function firstProductImageId(int $productId): ?int
    {
        $imageId = DB::table('product_images')
            ->join('product_variants', 'product_images.product_variant_id', '=', 'product_variants.id')
            ->where('product_variants.product_id', $productId)
            ->orderByDesc('product_images.is_primary')
            ->orderBy('product_images.sort_order')
            ->orderBy('product_images.id')
            ->value('product_images.id');

        return $imageId === null ? null : (int) $imageId;
    }
};
