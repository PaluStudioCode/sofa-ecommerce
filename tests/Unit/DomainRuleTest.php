<?php

namespace Tests\Unit;

use App\Models\Order;
use App\Models\ProductVariant;
use App\Models\Shipment;
use App\Services\Midtrans\HttpMidtransPaymentGateway;
use App\Services\Orders\OrderStatusTransitionService;
use App\Services\Shipping\ShipmentStatusTransitionService;
use App\Services\Vouchers\VoucherStatusService;
use App\Support\GeoDistance;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class DomainRuleTest extends TestCase
{
    use RefreshDatabase;

    public function test_stock_available_never_goes_below_zero(): void
    {
        $variant = ProductVariant::factory()->make([
            'stock' => 3,
            'reserved_stock' => 5,
        ]);

        $this->assertSame(0, $variant->availableStock());

        $variant->reserved_stock = 1;

        $this->assertSame(2, $variant->availableStock());
    }

    public function test_voucher_status_normalization_handles_expiry_and_quota(): void
    {
        $service = new VoucherStatusService();

        $expired = $service->normalize([
            'status' => 'aktif',
            'end_at' => now()->subMinute(),
            'quota' => 10,
        ], usedCount: 0);

        $quotaFull = $service->normalize([
            'status' => 'aktif',
            'end_at' => now()->addDay(),
            'quota' => 2,
        ], usedCount: 2);

        $this->assertSame('kedaluwarsa', $expired['status']);
        $this->assertSame('kuota_habis', $quotaFull['status']);
    }

    public function test_midtrans_status_mapping_and_signature_validation(): void
    {
        config(['services.midtrans.server_key' => 'server-key']);
        $gateway = new HttpMidtransPaymentGateway(new HttpFactory());

        $payload = [
            'order_id' => 'ORDER-1-ATTEMPT-1',
            'status_code' => '200',
            'gross_amount' => '1500000.00',
        ];
        $payload['signature_key'] = hash('sha512', 'ORDER-1-ATTEMPT-1'.'200'.'1500000.00'.'server-key');

        $this->assertTrue($gateway->verifyCallbackSignature($payload));
        $this->assertFalse($gateway->verifyCallbackSignature([...$payload, 'signature_key' => 'invalid']));
        $this->assertSame('success', $gateway->mapTransactionStatus('settlement'));
        $this->assertSame('success', $gateway->mapTransactionStatus('capture'));
        $this->assertSame('pending', $gateway->mapTransactionStatus('pending'));
        $this->assertSame('expired', $gateway->mapTransactionStatus('expire'));
        $this->assertSame('cancelled', $gateway->mapTransactionStatus('cancel'));
        $this->assertSame('failed', $gateway->mapTransactionStatus('deny'));
        $this->assertSame('failed', $gateway->mapTransactionStatus('failure'));
    }

    public function test_haversine_distance_supports_shipping_radius_rules(): void
    {
        $nearDistance = GeoDistance::haversineMeters(-6.2, 106.816666, -6.2005, 106.816666);
        $farDistance = GeoDistance::haversineMeters(-6.2, 106.816666, -7.2, 107.816666);

        $this->assertLessThan(1000, $nearDistance);
        $this->assertGreaterThan(100000, $farDistance);
    }

    public function test_order_status_transition_rules_accept_valid_and_reject_invalid_paths(): void
    {
        $service = app(OrderStatusTransitionService::class);
        $order = Order::factory()->paid()->create();

        $service->updateByAdmin($order, 'diproses');

        $this->assertSame('diproses', $order->fresh()->order_status);

        $this->expectException(ValidationException::class);

        $service->updateByAdmin($order->fresh(), 'selesai');
    }

    public function test_shipment_status_transition_rules_accept_valid_and_reject_invalid_paths(): void
    {
        $service = new ShipmentStatusTransitionService();
        $shipment = Shipment::factory()->make(['status' => 'dijadwalkan']);

        $service->assertCanTransition($shipment, 'dalam_pengiriman');

        $this->expectException(ValidationException::class);

        $service->assertCanTransition($shipment, 'terkirim');
    }
}
