<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\LandingSection;
use App\Models\Notification;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use App\Models\Shipment;
use App\Models\Store;
use App\Models\User;
use App\Models\Voucher;
use App\Models\VoucherUsage;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $password = Hash::make('password');

        $customer = User::updateOrCreate(['email' => 'customer@sofa.test'], [
            'name' => 'Customer Demo',
            'phone' => '081234567890',
            'role' => 'customer',
            'password' => $password,
            'email_verified_at' => now(),
        ]);

        User::updateOrCreate(['email' => 'admin@sofa.test'], [
            'name' => 'Admin Demo',
            'phone' => '081234567891',
            'role' => 'admin',
            'password' => $password,
            'email_verified_at' => now(),
        ]);

        User::updateOrCreate(['email' => 'owner@sofa.test'], [
            'name' => 'Owner Demo',
            'phone' => '081234567892',
            'role' => 'owner',
            'password' => $password,
            'email_verified_at' => now(),
        ]);

        $category = Category::updateOrCreate(['slug' => 'sofa-ruang-tamu'], [
            'name' => 'Sofa Ruang Tamu',
            'description' => 'Koleksi sofa utama untuk ruang keluarga dan ruang tamu.',
            'is_active' => true,
        ]);

        $featuredProduct = Product::updateOrCreate(['slug' => 'sofa-luna-3-seater'], [
            'category_id' => $category->id,
            'name' => 'Sofa Luna 3 Seater',
            'description' => 'Sofa tiga dudukan dengan busa empuk, rangka kokoh, dan desain minimalis.',
            'status' => 'aktif',
            'is_featured' => true,
        ]);

        Product::updateOrCreate(['slug' => 'sofa-arsip-nonaktif'], [
            'category_id' => $category->id,
            'name' => 'Sofa Arsip Nonaktif',
            'description' => 'Contoh produk nonaktif untuk kebutuhan demo dashboard.',
            'status' => 'nonaktif',
            'is_featured' => false,
        ]);

        $activeVariant = ProductVariant::updateOrCreate(['sku' => 'LUNA-3S-LINEN-GREY'], [
            'product_id' => $featuredProduct->id,
            'variant_name' => 'Linen Abu 3 Seater',
            'size' => '3 Seater',
            'material' => 'Linen',
            'color' => 'Abu-abu',
            'price' => 4500000,
            'stock' => 12,
            'reserved_stock' => 1,
            'status' => 'aktif',
        ]);

        ProductVariant::updateOrCreate(['sku' => 'LUNA-3S-VELVET-NAVY'], [
            'product_id' => $featuredProduct->id,
            'variant_name' => 'Velvet Navy 3 Seater',
            'size' => '3 Seater',
            'material' => 'Velvet',
            'color' => 'Navy',
            'price' => 5200000,
            'stock' => 0,
            'reserved_stock' => 0,
            'status' => 'stok_habis',
        ]);

        ProductImage::updateOrCreate([
            'product_id' => $featuredProduct->id,
            'is_primary' => true,
        ], [
            'file_path' => 'https://images.unsplash.com/photo-1555041469-a586c61ea9bc?auto=format&fit=crop&w=1200&q=80',
            'product_variant_id' => null,
            'alt_text' => 'Sofa Luna 3 Seater',
            'sort_order' => 0,
            'is_primary' => true,
        ]);

        $activeVoucher = Voucher::updateOrCreate(['code' => 'SOFAHEMAT'], [
            'name' => 'Sofa Hemat',
            'description' => 'Potongan demo untuk pembelian sofa.',
            'discount_type' => 'nominal',
            'discount_value' => 250000,
            'max_discount' => null,
            'minimum_purchase' => 3000000,
            'quota' => 100,
            'used_count' => 1,
            'per_user_limit' => 1,
            'start_at' => now()->subDay(),
            'end_at' => now()->addMonth(),
            'status' => 'aktif',
        ]);

        Voucher::updateOrCreate(['code' => 'LAMASOFA'], [
            'name' => 'Voucher Lama Sofa',
            'description' => 'Voucher demo yang sudah kedaluwarsa.',
            'discount_type' => 'percentage',
            'discount_value' => 10,
            'max_discount' => 300000,
            'minimum_purchase' => 2000000,
            'quota' => 50,
            'used_count' => 0,
            'per_user_limit' => 1,
            'start_at' => now()->subMonths(2),
            'end_at' => now()->subMonth(),
            'status' => 'kedaluwarsa',
        ]);

        $store = Store::updateOrCreate(['name' => 'Toko Jakarta Pusat Demo'], [
            'description' => 'Titik toko dan radius layanan aktif untuk demo checkout.',
            'latitude' => -6.20000000,
            'longitude' => 106.81666600,
            'radius_km' => 15,
            'shipping_cost' => 150000,
            'priority' => 10,
            'is_active' => true,
        ]);

        LandingSection::updateOrCreate(['section_key' => 'hero'], [
            'title' => 'Sofa nyaman untuk rumah yang hidup',
            'subtitle' => 'Koleksi sofa pilihan dengan pengiriman internal toko.',
            'content' => 'Pilih sofa, checkout, bayar online, lalu tunggu jadwal pengiriman dari tim kami.',
            'image_path' => 'https://images.unsplash.com/photo-1493663284031-b7e3aefcae8e?auto=format&fit=crop&w=1600&q=80',
            'button_label' => 'Lihat Katalog',
            'button_url' => '/catalog',
            'sort_order' => 0,
            'is_active' => true,
        ]);

        $order = Order::updateOrCreate(['order_number' => 'ORD-DEMO-0001'], [
            'user_id' => $customer->id,
            'voucher_id' => $activeVoucher->id,
            'store_id' => $store->id,
            'customer_name' => $customer->name,
            'customer_phone' => $customer->phone,
            'shipping_address' => 'Jl. Contoh Sofa No. 1, Jakarta Pusat, DKI Jakarta',
            'shipping_city' => 'Jakarta Pusat',
            'shipping_district' => 'Gambir',
            'shipping_postal_code' => '10110',
            'shipping_latitude' => -6.20000000,
            'shipping_longitude' => 106.81666600,
            'shipping_note' => 'Rumah pagar hitam, hubungi sebelum datang.',
            'subtotal_amount' => 4500000,
            'discount_amount' => 250000,
            'shipping_cost' => 150000,
            'total_amount' => 4400000,
            'order_status' => 'menunggu_pembayaran',
            'payment_status' => 'pending',
        ]);

        OrderItem::updateOrCreate([
            'order_id' => $order->id,
            'product_variant_id' => $activeVariant->id,
        ], [
            'product_id' => $featuredProduct->id,
            'product_name' => $featuredProduct->name,
            'variant_name' => $activeVariant->variant_name,
            'variant_sku' => $activeVariant->sku,
            'variant_size' => $activeVariant->size,
            'variant_material' => $activeVariant->material,
            'variant_color' => $activeVariant->color,
            'product_price' => 4500000,
            'quantity' => 1,
            'subtotal' => 4500000,
        ]);

        VoucherUsage::updateOrCreate(['order_id' => $order->id], [
            'voucher_id' => $activeVoucher->id,
            'user_id' => $customer->id,
            'discount_amount' => 250000,
            'used_at' => now(),
        ]);

        Payment::updateOrCreate(['midtrans_order_id' => 'ORD-DEMO-0001-ATTEMPT-1'], [
            'order_id' => $order->id,
            'attempt_number' => 1,
            'midtrans_transaction_id' => null,
            'payment_type' => null,
            'status' => 'pending',
            'transaction_status' => 'pending',
            'fraud_status' => null,
            'gross_amount' => 4400000,
            'snap_token' => 'fake-snap-token-ord-demo-0001',
            'redirect_url' => null,
            'paid_at' => null,
            'expired_at' => now()->addDay(),
            'raw_response' => ['source' => 'demo-seeder'],
        ]);

        Shipment::updateOrCreate(['order_id' => $order->id], [
            'status' => 'belum_dijadwalkan',
            'scheduled_at' => null,
            'delivered_at' => null,
            'driver_name' => null,
            'driver_phone' => null,
            'vehicle_note' => null,
            'shipping_note' => null,
        ]);

        Notification::updateOrCreate([
            'order_id' => $order->id,
            'channel' => 'whatsapp',
            'event_type' => 'order_created',
            'recipient' => $customer->phone,
        ], [
            'user_id' => $customer->id,
            'message' => 'Pesanan demo ORD-DEMO-0001 berhasil dibuat dan menunggu pembayaran.',
            'status' => 'pending',
            'provider' => 'fonnte',
            'provider_response' => null,
            'sent_at' => null,
        ]);
    }
}
