<?php

namespace Tests\Feature;

use App\Models\CartItem;
use App\Models\Notification;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Store;
use App\Models\User;
use App\Services\Fonnte\FonnteNotificationClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WhatsAppNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_checkout_creates_order_created_whatsapp_notification(): void
    {
        $client = $this->bindFonnteClient();
        $customer = User::factory()->create(['name' => 'Customer Notif']);
        [$product, $variant] = $this->activeProductAndVariant(price: 2000000, stock: 5);
        CartItem::factory()->create([
            'user_id' => $customer->id,
            'product_id' => $product->id,
            'product_variant_id' => $variant->id,
            'quantity' => 1,
        ]);
        Store::factory()->create([
            'latitude' => -6.2,
            'longitude' => 106.816666,
            'radius_km' => 10,
            'shipping_cost' => 100000,
            'is_active' => true,
        ]);

        $this->actingAs($customer)
            ->post(route('checkout.location'), $this->locationPayload())
            ->assertRedirect(route('checkout.index'));

        $this->actingAs($customer)
            ->post(route('checkout.store'), [
                'customer_phone' => '081234567890',
                'shipping_note' => null,
            ])
            ->assertRedirect();

        $order = Order::firstOrFail();

        $this->assertSame(1, $client->calls);
        $this->assertDatabaseHas('notifications', [
            'order_id' => $order->id,
            'user_id' => $customer->id,
            'channel' => 'whatsapp',
            'event_type' => 'order_created',
            'recipient' => '081234567890',
            'status' => 'sent',
            'provider' => 'fonnte',
        ]);
    }

    public function test_payment_success_notification_is_not_sent_twice_for_duplicate_callback(): void
    {
        $client = $this->bindFonnteClient();
        [, $order, $payment] = $this->payableOrder();
        $payload = $this->callbackPayload($payment, 'settlement');

        $this->postJson(route('midtrans.callback'), $payload)
            ->assertOk()
            ->assertJsonPath('status', 'success');

        $this->postJson(route('midtrans.callback'), $payload)
            ->assertOk()
            ->assertJsonPath('status', 'success');

        $this->assertSame(1, $client->calls);
        $this->assertSame(1, Notification::where('order_id', $order->id)->where('event_type', 'payment_success')->count());
        $this->assertDatabaseHas('notifications', [
            'order_id' => $order->id,
            'event_type' => 'payment_success',
            'status' => 'sent',
        ]);
    }

    public function test_order_status_notifications_are_sent_once_for_processing_shipped_and_completed(): void
    {
        $client = $this->bindFonnteClient();
        $admin = User::factory()->admin()->create();
        $order = Order::factory()->paid()->create([
            'customer_phone' => '081234567890',
            'order_status' => 'dibayar',
        ]);

        $this->actingAs($admin)
            ->put(route('admin.orders.update', $order), ['order_status' => 'diproses'])
            ->assertRedirect();

        $this->actingAs($admin)
            ->put(route('admin.orders.update', $order), ['order_status' => 'dikirim'])
            ->assertRedirect();

        $this->actingAs($admin)
            ->put(route('admin.orders.update', $order), ['order_status' => 'selesai'])
            ->assertRedirect();

        $this->actingAs($admin)
            ->put(route('admin.orders.update', $order), ['order_status' => 'selesai'])
            ->assertRedirect();

        $this->assertSame(3, $client->calls);
        $this->assertDatabaseHas('notifications', ['order_id' => $order->id, 'event_type' => 'order_processing', 'status' => 'sent']);
        $this->assertDatabaseHas('notifications', ['order_id' => $order->id, 'event_type' => 'order_shipped', 'status' => 'sent']);
        $this->assertDatabaseHas('notifications', ['order_id' => $order->id, 'event_type' => 'order_completed', 'status' => 'sent']);
    }

    public function test_shipment_status_notifications_are_sent_for_customer_tracking_statuses(): void
    {
        $client = $this->bindFonnteClient();
        $admin = User::factory()->admin()->create();
        $order = Order::factory()->paid()->create([
            'customer_phone' => '081234567890',
            'order_status' => 'dibayar',
        ]);
        $scheduledAt = now()->addDay()->format('Y-m-d\TH:i');
        $deliveredAt = now()->addDays(2)->format('Y-m-d\TH:i');

        $payload = [
            'scheduled_at' => null,
            'delivered_at' => null,
            'driver_name' => 'ACO',
            'driver_phone' => '081250677569',
            'vehicle_note' => 'Mobil Avanza',
            'shipping_note' => 'Hubungi customer sebelum tiba.',
        ];

        $this->actingAs($admin)
            ->put(route('admin.shipments.update', $order), [
                ...$payload,
                'status' => 'belum_dijadwalkan',
            ])
            ->assertRedirect();

        $this->actingAs($admin)
            ->put(route('admin.shipments.update', $order), [
                ...$payload,
                'status' => 'dijadwalkan',
                'scheduled_at' => $scheduledAt,
            ])
            ->assertRedirect();

        $this->actingAs($admin)
            ->put(route('admin.shipments.update', $order), [
                ...$payload,
                'status' => 'dalam_pengiriman',
                'scheduled_at' => $scheduledAt,
            ])
            ->assertRedirect();

        $this->actingAs($admin)
            ->put(route('admin.shipments.update', $order), [
                ...$payload,
                'status' => 'gagal_dikirim',
                'scheduled_at' => $scheduledAt,
            ])
            ->assertRedirect();

        $this->actingAs($admin)
            ->put(route('admin.shipments.update', $order), [
                ...$payload,
                'status' => 'dijadwalkan',
                'scheduled_at' => $scheduledAt,
            ])
            ->assertRedirect();

        $this->actingAs($admin)
            ->put(route('admin.shipments.update', $order), [
                ...$payload,
                'status' => 'dalam_pengiriman',
                'scheduled_at' => $scheduledAt,
            ])
            ->assertRedirect();

        $this->actingAs($admin)
            ->put(route('admin.shipments.update', $order), [
                ...$payload,
                'status' => 'terkirim',
                'scheduled_at' => $scheduledAt,
                'delivered_at' => $deliveredAt,
            ])
            ->assertRedirect();

        $this->assertSame(5, $client->calls);
        $this->assertDatabaseHas('notifications', ['order_id' => $order->id, 'event_type' => 'shipment_unscheduled', 'status' => 'sent']);
        $this->assertDatabaseHas('notifications', ['order_id' => $order->id, 'event_type' => 'shipment_scheduled', 'status' => 'sent']);
        $this->assertDatabaseHas('notifications', ['order_id' => $order->id, 'event_type' => 'shipment_in_transit', 'status' => 'sent']);
        $this->assertDatabaseHas('notifications', ['order_id' => $order->id, 'event_type' => 'shipment_failed', 'status' => 'sent']);
        $this->assertDatabaseHas('notifications', ['order_id' => $order->id, 'event_type' => 'shipment_delivered', 'status' => 'sent']);
        $this->assertSame('selesai', $order->fresh()->order_status);
    }

    public function test_fonnte_failure_is_recorded_and_does_not_cancel_order_update(): void
    {
        $this->bindFonnteClient(shouldFail: true);
        $admin = User::factory()->admin()->create();
        $order = Order::factory()->paid()->create([
            'customer_phone' => '081234567890',
            'order_status' => 'dibayar',
        ]);

        $this->actingAs($admin)
            ->put(route('admin.orders.update', $order), ['order_status' => 'diproses'])
            ->assertRedirect();

        $this->assertSame('diproses', $order->fresh()->order_status);
        $this->assertDatabaseHas('notifications', [
            'order_id' => $order->id,
            'event_type' => 'order_processing',
            'status' => 'failed',
        ]);
    }

    private function locationPayload(): array
    {
        return [
            'latitude' => -6.2,
            'longitude' => 106.816666,
            'formatted_address' => 'Jl. Contoh Sofa No. 1, Jakarta, Indonesia',
            'city' => 'Jakarta',
            'district' => 'Gambir',
            'postal_code' => '10110',
        ];
    }

    private function bindFonnteClient(bool $shouldFail = false): object
    {
        $client = new class($shouldFail) implements FonnteNotificationClient {
            public int $calls = 0;

            public function __construct(private readonly bool $shouldFail)
            {
            }

            public function sendWhatsApp(string $target, string $message, array $options = []): array
            {
                $this->calls++;

                if ($this->shouldFail) {
                    throw new \RuntimeException('Fake Fonnte failure.');
                }

                return [
                    'status' => true,
                    'detail' => 'accepted',
                    'target' => $target,
                    'message' => $message,
                    'options' => $options,
                ];
            }
        };

        $this->app->instance(FonnteNotificationClient::class, $client);

        return $client;
    }

    private function activeProductAndVariant(int $price, int $stock, int $reserved = 0): array
    {
        $product = Product::factory()->create(['status' => 'aktif']);
        $variant = ProductVariant::factory()->for($product)->create([
            'variant_name' => 'Premium',
            'price' => $price,
            'stock' => $stock,
            'reserved_stock' => $reserved,
            'status' => 'aktif',
        ]);

        return [$product, $variant];
    }

    private function payableOrder(): array
    {
        $customer = User::factory()->create();
        [$product, $variant] = $this->activeProductAndVariant(price: 1000000, stock: 5, reserved: 1);
        $order = Order::factory()->for($customer)->create([
            'order_status' => 'menunggu_pembayaran',
            'payment_status' => 'pending',
            'customer_phone' => '081234567890',
            'subtotal_amount' => 1000000,
            'discount_amount' => 0,
            'shipping_cost' => 100000,
            'total_amount' => 1100000,
        ]);
        OrderItem::factory()->for($order)->create([
            'product_id' => $product->id,
            'product_variant_id' => $variant->id,
            'product_name' => $product->name,
            'variant_name' => $variant->variant_name,
            'product_price' => 1000000,
            'quantity' => 1,
            'subtotal' => 1000000,
        ]);
        $payment = Payment::factory()->for($order)->create([
            'attempt_number' => 1,
            'gross_amount' => 1100000,
            'status' => 'pending',
            'transaction_status' => 'pending',
        ]);

        return [$customer, $order, $payment];
    }

    private function callbackPayload(Payment $payment, string $transactionStatus): array
    {
        return [
            'order_id' => $payment->midtrans_order_id,
            'transaction_id' => 'TRX-'.$payment->id,
            'transaction_status' => $transactionStatus,
            'fraud_status' => 'accept',
            'payment_type' => 'bank_transfer',
            'gross_amount' => number_format((float) $payment->gross_amount, 2, '.', ''),
            'signature_valid' => true,
        ];
    }
}
