<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use App\Models\ShippingSetting;
use App\Models\User;
use App\Models\UserAddress;
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

        $customer = User::updateOrCreate(['email' => 'user@sofa.com'], [
            'name' => 'User Sofa',
            'phone' => '081245000002',
            'role' => 'customer',
            'password' => $password,
            'email_verified_at' => now(),
        ]);

        UserAddress::updateOrCreate(['user_id' => $customer->id, 'is_default' => true], [
            'recipient_name' => 'User Sofa',
            'phone' => '081245000002',
            'detail' => 'Rumah pagar putih, dekat minimarket.',
            'formatted_address' => 'Jl. Moh. Yamin, Palu, Sulawesi Tengah',
            'city' => 'Palu',
            'district' => 'Palu Selatan',
            'postal_code' => '94111',
            'latitude' => -0.90030000,
            'longitude' => 119.87800000,
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

        $lunaNavy = ProductVariant::updateOrCreate(['sku' => 'LUNA-3S-VELVET-NAVY'], [
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

        $naraSage = ProductVariant::updateOrCreate(['sku' => 'NARA-2S-LINEN-SAGE'], [
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

        $this->seedVariantImages($lunaGrey, [
            ['url' => 'https://images.unsplash.com/photo-1555041469-a586c61ea9bc?auto=format&fit=crop&w=1200&q=80', 'alt' => 'Sofa Luna linen abu-abu tampak depan'],
            ['url' => 'https://images.unsplash.com/photo-1540932239986-30128078f3c5?auto=format&fit=crop&w=1200&q=80', 'alt' => 'Sofa Luna linen abu-abu di ruang tamu'],
            ['url' => 'https://images.unsplash.com/photo-1493663284031-b7e3aefcae8e?auto=format&fit=crop&w=1200&q=80', 'alt' => 'Detail dudukan Sofa Luna linen abu-abu'],
        ]);

        $this->seedVariantImages($lunaNavy, [
            ['url' => 'https://images.unsplash.com/photo-1550581190-9c1c48d21d6c?auto=format&fit=crop&w=1200&q=80', 'alt' => 'Sofa Luna velvet navy tampak depan'],
            ['url' => 'https://images.unsplash.com/photo-1567016376408-0226e4d0c1ea?auto=format&fit=crop&w=1200&q=80', 'alt' => 'Sofa Luna velvet navy tampak samping'],
            ['url' => 'https://images.unsplash.com/photo-1512212621149-107ffe572d2f?auto=format&fit=crop&w=1200&q=80', 'alt' => 'Sofa Luna velvet navy dalam ruang keluarga'],
        ]);

        $this->seedVariantImages($auroraSand, [
            ['url' => 'https://images.unsplash.com/photo-1493663284031-b7e3aefcae8e?auto=format&fit=crop&w=1200&q=80', 'alt' => 'Sofa Aurora corner sand tampak depan'],
            ['url' => 'https://images.unsplash.com/photo-1600607687939-ce8a6c25118c?auto=format&fit=crop&w=1200&q=80', 'alt' => 'Sofa Aurora corner sand pada ruang keluarga'],
            ['url' => 'https://images.unsplash.com/photo-1600566753190-17f0baa2a6c3?auto=format&fit=crop&w=1200&q=80', 'alt' => 'Sofa Aurora corner sand tampak sudut'],
        ]);

        $this->seedVariantImages($naraIvory, [
            ['url' => 'https://images.unsplash.com/photo-1540574163026-643ea20ade25?auto=format&fit=crop&w=1200&q=80', 'alt' => 'Sofa Nara boucle ivory tampak depan'],
            ['url' => 'https://images.unsplash.com/photo-1616486338812-3dadae4b4ace?auto=format&fit=crop&w=1200&q=80', 'alt' => 'Sofa Nara boucle ivory dalam ruangan'],
            ['url' => 'https://images.unsplash.com/photo-1618220179428-22790b461013?auto=format&fit=crop&w=1200&q=80', 'alt' => 'Detail Sofa Nara boucle ivory'],
        ]);

        $this->seedVariantImages($naraSage, [
            ['url' => 'https://images.unsplash.com/photo-1550254478-ead40cc54513?auto=format&fit=crop&w=1200&q=80', 'alt' => 'Sofa Nara linen sage tampak depan'],
            ['url' => 'https://images.unsplash.com/photo-1618220252344-8ec99ec624b1?auto=format&fit=crop&w=1200&q=80', 'alt' => 'Sofa Nara linen sage tampak samping'],
            ['url' => 'https://images.unsplash.com/photo-1618221195710-dd6b41faaea6?auto=format&fit=crop&w=1200&q=80', 'alt' => 'Sofa Nara linen sage di ruang santai'],
        ]);

        $this->seedVariantImages($terraCharcoal, [
            ['url' => 'https://images.unsplash.com/photo-1550581190-9c1c48d21d6c?auto=format&fit=crop&w=1200&q=80', 'alt' => 'Sofa Terra bed charcoal mode sofa'],
            ['url' => 'https://images.unsplash.com/photo-1600210491892-03d54c0aaf87?auto=format&fit=crop&w=1200&q=80', 'alt' => 'Sofa Terra bed charcoal dalam ruang tamu'],
            ['url' => 'https://images.unsplash.com/photo-1618221118493-9cfa1a1c00da?auto=format&fit=crop&w=1200&q=80', 'alt' => 'Detail Sofa Terra bed charcoal'],
        ]);

        $this->seedVariantImages($osloBrown, [
            ['url' => 'https://images.unsplash.com/photo-1550254478-ead40cc54513?auto=format&fit=crop&w=1200&q=80', 'alt' => 'Sofa Oslo recliner cokelat tampak depan'],
            ['url' => 'https://images.unsplash.com/photo-1616627561839-074385245ff6?auto=format&fit=crop&w=1200&q=80', 'alt' => 'Sofa Oslo recliner cokelat dalam ruang santai'],
            ['url' => 'https://images.unsplash.com/photo-1615873968403-89e068629265?auto=format&fit=crop&w=1200&q=80', 'alt' => 'Detail Sofa Oslo recliner cokelat'],
        ]);

        $this->seedVariantImages($mikaEmerald, [
            ['url' => 'https://images.unsplash.com/photo-1567016432779-094069958ea5?auto=format&fit=crop&w=1200&q=80', 'alt' => 'Sofa Mika velvet emerald tampak depan'],
            ['url' => 'https://images.unsplash.com/photo-1618220179428-22790b461013?auto=format&fit=crop&w=1200&q=80', 'alt' => 'Sofa Mika velvet emerald di ruang tamu'],
            ['url' => 'https://images.unsplash.com/photo-1618221195710-dd6b41faaea6?auto=format&fit=crop&w=1200&q=80', 'alt' => 'Detail Sofa Mika velvet emerald'],
        ]);

        Voucher::updateOrCreate(['code' => 'SOFAHEMAT'], [
            'name' => 'Sofa Hemat',
            'description' => 'Potongan untuk pembelian sofa pilihan.',
            'discount_type' => 'nominal',
            'discount_value' => 250000,
            'max_discount' => null,
            'minimum_purchase' => 3000000,
            'quota' => 100,
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
            'per_user_limit' => 1,
            'start_at' => now()->subDay(),
            'end_at' => now()->addMonth(),
            'status' => 'aktif',
        ]);

        ShippingSetting::updateOrCreate(['origin_name' => 'Toko Sofa Palu'], [
            'origin_address' => 'Titik toko dan pusat pengiriman SofaStore di Palu, Sulawesi Tengah.',
            'origin_latitude' => -0.90030000,
            'origin_longitude' => 119.87800000,
            'radius_km' => 25,
            'shipping_cost_per_km' => 12000,
            'is_active' => true,
        ]);
    }

    private function seedVariantImages(ProductVariant $variant, array $images): void
    {
        foreach ($images as $index => $image) {
            ProductImage::updateOrCreate([
                'product_variant_id' => $variant->id,
                'file_path' => $image['url'],
            ], [
                'alt_text' => $image['alt'],
                'sort_order' => $index,
                'is_primary' => $index === 0,
            ]);
        }
    }
}
