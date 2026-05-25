<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Payment;
use App\Models\SystemSetting;
use App\Models\User;
use App\Services\Fonnte\FonnteNotificationClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StorePaymentNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_successful_payment_callback_sends_store_notification_with_admin_order_link(): void
    {
        $client = $this->bindCapturingFonnteClient();
        SystemSetting::updateStoreContact([
            'name' => 'SofaStore',
            'address' => 'Palu',
            'email' => 'store@example.test',
            'whatsapp' => '082200000000',
            'hours' => '09.00-18.00',
        ]);

        $customer = User::query()->create([
            'name' => 'Budi Sofa',
            'email' => 'budi@example.test',
            'phone' => '081234567890',
            'password' => 'password',
            'role' => 'customer',
            'email_verified_at' => now(),
        ]);

        $order = Order::query()->create([
            'order_number' => 'ORD-STORE-NOTIF-001',
            'user_id' => $customer->id,
            'order_status' => 'menunggu_pembayaran',
        ]);

        $order->total()->create([
            'subtotal_amount' => 1000000,
            'discount_amount' => 0,
            'shipping_cost' => 100000,
            'total_amount' => 1100000,
        ]);

        $payment = Payment::query()->create([
            'order_id' => $order->id,
            'attempt_number' => 1,
            'midtrans_order_id' => 'ORD-STORE-NOTIF-001-ATTEMPT-1',
            'status' => 'pending',
            'transaction_status' => 'pending',
            'gross_amount' => 1100000,
        ]);

        $this->postJson(route('midtrans.callback'), [
            'order_id' => $payment->midtrans_order_id,
            'transaction_id' => 'TRX-STORE-NOTIF-001',
            'transaction_status' => 'settlement',
            'fraud_status' => 'accept',
            'payment_type' => 'bank_transfer',
            'gross_amount' => '1100000.00',
            'signature_valid' => true,
        ])
            ->assertOk()
            ->assertJsonPath('status', 'success');

        $this->assertCount(2, $client->calls);

        $storeCall = $client->calls[1];
        $this->assertSame('082200000000', $storeCall['target']);
        $this->assertStringContainsString('Pembayaran pesanan ORD-STORE-NOTIF-001 sudah berhasil diterima.', $storeCall['message']);
        $this->assertStringContainsString('Pelanggan: Budi Sofa', $storeCall['message']);
        $this->assertStringContainsString('Total: Rp 1.100.000', $storeCall['message']);
        $this->assertStringContainsString(route('admin.orders.show', $order), $storeCall['message']);
        $this->assertSame('store_payment_success', $storeCall['options']['event']);
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
}
