<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use App\Models\Store;
use App\Models\User;
use App\Models\Voucher;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $password = Hash::make('password');

        User::updateOrCreate(['email' => 'admin@sofa.com'], [
            'name' => 'Admin Sofa',
            'phone' => '081245000001',
            'role' => 'admin',
            'password' => $password,
            'email_verified_at' => now(),
        ]);

        User::updateOrCreate(['email' => 'user@sofa.com'], [
            'name' => 'User Sofa',
            'phone' => '081245000002',
            'role' => 'customer',
            'shipping_address' => 'Jl. Moh. Yamin, Palu, Sulawesi Tengah',
            'shipping_city' => 'Palu',
            'shipping_district' => 'Palu Selatan',
            'shipping_postal_code' => '94111',
            'shipping_latitude' => -0.90030000,
            'shipping_longitude' => 119.87800000,
            'password' => $password,
            'email_verified_at' => now(),
        ]);

        $livingRoom = Category::updateOrCreate(['slug' => 'sofa-ruang-tamu'], [
            'name' => 'Sofa Ruang Tamu',
            'description' => 'Sofa utama untuk ruang keluarga dan ruang tamu.',
            'is_active' => true,
        ]);

        $corner = Category::updateOrCreate(['slug' => 'sofa-sudut'], [
            'name' => 'Sofa Sudut',
            'description' => 'Pilihan sofa L dan sectional untuk sudut ruangan.',
            'is_active' => true,
        ]);

        $sofaBed = Category::updateOrCreate(['slug' => 'sofa-bed'], [
            'name' => 'Sofa Bed',
            'description' => 'Sofa multifungsi yang bisa digunakan untuk duduk dan tidur.',
            'is_active' => true,
        ]);

        $recliner = Category::updateOrCreate(['slug' => 'sofa-recliner'], [
            'name' => 'Sofa Recliner',
            'description' => 'Sofa santai dengan sandaran dan pijakan kaki yang nyaman.',
            'is_active' => true,
        ]);

        $luna = Product::updateOrCreate(['slug' => 'sofa-luna-3-seater'], [
            'category_id' => $livingRoom->id,
            'name' => 'Sofa Luna 3 Seater',
            'description' => 'Sofa tiga dudukan dengan rangka kokoh, busa empuk, dan desain minimalis.',
            'status' => 'aktif',
            'is_featured' => true,
        ]);

        $aurora = Product::updateOrCreate(['slug' => 'sofa-aurora-corner'], [
            'category_id' => $corner->id,
            'name' => 'Sofa Aurora Corner',
            'description' => 'Sofa sudut luas untuk ruang keluarga yang sering dipakai berkumpul.',
            'status' => 'aktif',
            'is_featured' => true,
        ]);

        $nara = Product::updateOrCreate(['slug' => 'sofa-nara-2-seater'], [
            'category_id' => $livingRoom->id,
            'name' => 'Sofa Nara 2 Seater',
            'description' => 'Sofa dua dudukan ringkas untuk apartemen, ruang baca, dan ruang tamu kecil.',
            'status' => 'aktif',
            'is_featured' => true,
        ]);

        $terra = Product::updateOrCreate(['slug' => 'sofa-terra-bed'], [
            'category_id' => $sofaBed->id,
            'name' => 'Sofa Terra Bed',
            'description' => 'Sofa bed praktis dengan mekanisme lipat mudah untuk tamu menginap.',
            'status' => 'aktif',
            'is_featured' => false,
        ]);

        $oslo = Product::updateOrCreate(['slug' => 'sofa-oslo-recliner'], [
            'category_id' => $recliner->id,
            'name' => 'Sofa Oslo Recliner',
            'description' => 'Sofa recliner personal dengan busa tebal untuk menonton dan bersantai.',
            'status' => 'aktif',
            'is_featured' => false,
        ]);

        $mika = Product::updateOrCreate(['slug' => 'sofa-mika-loveseat'], [
            'category_id' => $livingRoom->id,
            'name' => 'Sofa Mika Loveseat',
            'description' => 'Loveseat elegan dengan warna aksen untuk mempercantik sudut ruangan.',
            'status' => 'aktif',
            'is_featured' => true,
        ]);

        $lunaGrey = ProductVariant::updateOrCreate(['sku' => 'LUNA-3S-LINEN-GREY'], [
            'product_id' => $luna->id,
            'variant_name' => 'Linen Abu 3 Seater',
            'size' => '3 Seater',
            'material' => 'Linen',
            'color' => 'Abu-abu',
            'price' => 4500000,
            'stock' => 12,
            'reserved_stock' => 0,
            'status' => 'aktif',
        ]);

        ProductVariant::updateOrCreate(['sku' => 'LUNA-3S-VELVET-NAVY'], [
            'product_id' => $luna->id,
            'variant_name' => 'Velvet Navy 3 Seater',
            'size' => '3 Seater',
            'material' => 'Velvet',
            'color' => 'Navy',
            'price' => 5200000,
            'stock' => 8,
            'reserved_stock' => 0,
            'status' => 'aktif',
        ]);

        $auroraSand = ProductVariant::updateOrCreate(['sku' => 'AURORA-L-CHENILLE-SAND'], [
            'product_id' => $aurora->id,
            'variant_name' => 'Chenille Sand L Shape',
            'size' => 'L Shape 260 cm',
            'material' => 'Chenille',
            'color' => 'Sand',
            'price' => 7800000,
            'stock' => 6,
            'reserved_stock' => 0,
            'status' => 'aktif',
        ]);

        $naraIvory = ProductVariant::updateOrCreate(['sku' => 'NARA-2S-BOUCLE-IVORY'], [
            'product_id' => $nara->id,
            'variant_name' => 'Boucle Ivory 2 Seater',
            'size' => '2 Seater',
            'material' => 'Boucle',
            'color' => 'Ivory',
            'price' => 3800000,
            'stock' => 10,
            'reserved_stock' => 0,
            'status' => 'aktif',
        ]);

        ProductVariant::updateOrCreate(['sku' => 'NARA-2S-LINEN-SAGE'], [
            'product_id' => $nara->id,
            'variant_name' => 'Linen Sage 2 Seater',
            'size' => '2 Seater',
            'material' => 'Linen',
            'color' => 'Sage',
            'price' => 3950000,
            'stock' => 7,
            'reserved_stock' => 0,
            'status' => 'aktif',
        ]);

        $terraCharcoal = ProductVariant::updateOrCreate(['sku' => 'TERRA-SB-MICROFIBER-CHARCOAL'], [
            'product_id' => $terra->id,
            'variant_name' => 'Microfiber Charcoal Sofa Bed',
            'size' => 'Sofa Bed 190 cm',
            'material' => 'Microfiber',
            'color' => 'Charcoal',
            'price' => 6100000,
            'stock' => 5,
            'reserved_stock' => 0,
            'status' => 'aktif',
        ]);

        $osloBrown = ProductVariant::updateOrCreate(['sku' => 'OSLO-1R-LEATHER-BROWN'], [
            'product_id' => $oslo->id,
            'variant_name' => 'Leather Brown Recliner',
            'size' => '1 Seater Recliner',
            'material' => 'Kulit sintetis',
            'color' => 'Cokelat',
            'price' => 5600000,
            'stock' => 4,
            'reserved_stock' => 0,
            'status' => 'aktif',
        ]);

        $mikaEmerald = ProductVariant::updateOrCreate(['sku' => 'MIKA-2S-VELVET-EMERALD'], [
            'product_id' => $mika->id,
            'variant_name' => 'Velvet Emerald Loveseat',
            'size' => 'Loveseat',
            'material' => 'Velvet',
            'color' => 'Emerald',
            'price' => 4300000,
            'stock' => 9,
            'reserved_stock' => 0,
            'status' => 'aktif',
        ]);

        ProductImage::updateOrCreate([
            'product_variant_id' => $lunaGrey->id,
            'file_path' => 'https://images.unsplash.com/photo-1555041469-a586c61ea9bc?auto=format&fit=crop&w=1200&q=80',
        ], [
            'alt_text' => 'Sofa Luna 3 Seater',
            'sort_order' => 0,
            'is_primary' => true,
        ]);

        ProductImage::updateOrCreate([
            'product_variant_id' => $auroraSand->id,
            'file_path' => 'https://images.unsplash.com/photo-1493663284031-b7e3aefcae8e?auto=format&fit=crop&w=1200&q=80',
        ], [
            'alt_text' => 'Sofa Aurora Corner',
            'sort_order' => 0,
            'is_primary' => true,
        ]);

        ProductImage::updateOrCreate([
            'product_variant_id' => $naraIvory->id,
            'file_path' => 'https://images.unsplash.com/photo-1540574163026-643ea20ade25?auto=format&fit=crop&w=1200&q=80',
        ], [
            'alt_text' => 'Sofa Nara 2 Seater',
            'sort_order' => 0,
            'is_primary' => true,
        ]);

        ProductImage::updateOrCreate([
            'product_variant_id' => $terraCharcoal->id,
            'file_path' => 'https://images.unsplash.com/photo-1550581190-9c1c48d21d6c?auto=format&fit=crop&w=1200&q=80',
        ], [
            'alt_text' => 'Sofa Terra Bed',
            'sort_order' => 0,
            'is_primary' => true,
        ]);

        ProductImage::updateOrCreate([
            'product_variant_id' => $osloBrown->id,
            'file_path' => 'https://images.unsplash.com/photo-1550254478-ead40cc54513?auto=format&fit=crop&w=1200&q=80',
        ], [
            'alt_text' => 'Sofa Oslo Recliner',
            'sort_order' => 0,
            'is_primary' => true,
        ]);

        ProductImage::updateOrCreate([
            'product_variant_id' => $mikaEmerald->id,
            'file_path' => 'https://images.unsplash.com/photo-1567016432779-094069958ea5?auto=format&fit=crop&w=1200&q=80',
        ], [
            'alt_text' => 'Sofa Mika Loveseat',
            'sort_order' => 0,
            'is_primary' => true,
        ]);

        Voucher::updateOrCreate(['code' => 'SOFAHEMAT'], [
            'name' => 'Sofa Hemat',
            'description' => 'Potongan untuk pembelian sofa pilihan.',
            'discount_type' => 'nominal',
            'discount_value' => 250000,
            'max_discount' => null,
            'minimum_purchase' => 3000000,
            'quota' => 100,
            'used_count' => 0,
            'per_user_limit' => 1,
            'start_at' => now()->subDay(),
            'end_at' => now()->addMonth(),
            'status' => 'aktif',
        ]);

        Voucher::updateOrCreate(['code' => 'PALU10'], [
            'name' => 'Palu 10%',
            'description' => 'Diskon persentase untuk pelanggan area Palu.',
            'discount_type' => 'percentage',
            'discount_value' => 10,
            'max_discount' => 300000,
            'minimum_purchase' => 2500000,
            'quota' => 50,
            'used_count' => 0,
            'per_user_limit' => 1,
            'start_at' => now()->subDay(),
            'end_at' => now()->addMonth(),
            'status' => 'aktif',
        ]);

        Store::updateOrCreate(['name' => 'Toko Sofa Palu'], [
            'description' => 'Titik toko dan pusat pengiriman SofaStore di Palu, Sulawesi Tengah.',
            'latitude' => -0.90030000,
            'longitude' => 119.87800000,
            'radius_km' => 25,
            'shipping_cost' => 12000,
            'priority' => 0,
            'is_active' => true,
        ]);
    }
}
