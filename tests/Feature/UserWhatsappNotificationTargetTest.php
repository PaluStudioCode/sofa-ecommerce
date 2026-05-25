<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\User;
use App\Services\Fonnte\FonnteNotificationClient;
use App\Services\Notifications\WhatsAppNotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserWhatsappNotificationTargetTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_order_notifications_are_sent_to_user_profile_phone_first(): void
    {
        $client = $this->bindCapturingFonnteClient();
        $user = $this->createCustomer('080000000001');
        $order = $this->createOrderWithDifferentShippingPhone($user, '089999999999');

        app(WhatsAppNotificationService::class)->sendOrderEvent($order, 'order_created');

        $this->assertCount(1, $client->calls);
        $this->assertSame('080000000001', $client->calls[0]['target']);
        $this->assertStringContainsString('Pesanan ORD-WA-USER-001 berhasil dibuat.', $client->calls[0]['message']);
    }

    public function test_customer_order_notifications_fall_back_to_shipping_phone_when_user_phone_is_empty(): void
    {
        $client = $this->bindCapturingFonnteClient();
        $user = $this->createCustomer(null);
        $order = $this->createOrderWithDifferentShippingPhone($user, '089999999999');

        app(WhatsAppNotificationService::class)->sendOrderEvent($order, 'order_created');

        $this->assertCount(1, $client->calls);
        $this->assertSame('089999999999', $client->calls[0]['target']);
    }

    private function bindCapturingFonnteClient(): object
    {
        $client = new class implements FonnteNotificationClient {
            public array $calls = [];

            public function sendWhatsApp(string $target, string $message, array $options = []): array
            {
                $this->calls[] = compact('target', 'message', 'options');

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

    private function createCustomer(?string $phone): User
    {
        $user = new User();
        $user->forceFill([
            'name' => 'Customer WA',
            'email' => 'customer-wa@example.test',
            'phone' => $phone,
            'password' => 'password',
            'role' => 'customer',
            'email_verified_at' => now(),
        ])->save();

        return $user;
    }

    private function createOrderWithDifferentShippingPhone(User $user, string $shippingPhone): Order
    {
        $order = Order::query()->create([
            'order_number' => 'ORD-WA-USER-001',
            'user_id' => $user->id,
            'order_status' => 'menunggu_pembayaran',
        ]);

        $order->total()->create([
            'subtotal_amount' => 1000000,
            'discount_amount' => 0,
            'shipping_cost' => 100000,
            'total_amount' => 1100000,
        ]);

        $order->address()->create([
            'recipient_name' => 'Penerima Pengiriman',
            'phone' => $shippingPhone,
            'detail' => 'Rumah pagar putih',
            'formatted_address' => 'Jl. Sofa No. 1, Palu',
            'city' => 'Palu',
            'district' => 'Palu Selatan',
            'postal_code' => '94111',
            'latitude' => -0.9003,
            'longitude' => 119.878,
        ]);

        return $order;
    }
}
