<?php

namespace Tests\Feature;

use App\Services\Fonnte\FakeFonnteNotificationClient;
use App\Services\Fonnte\FonnteNotificationClient;
use App\Services\Midtrans\FakeMidtransPaymentGateway;
use App\Services\Midtrans\HttpMidtransPaymentGateway;
use App\Services\Midtrans\MidtransPaymentGateway;
use Illuminate\Http\Client\Factory as HttpFactory;
use Tests\TestCase;

class ExternalServiceFoundationTest extends TestCase
{
    public function test_external_services_can_resolve_fake_clients_for_tests(): void
    {
        config([
            'services.midtrans.driver' => 'fake',
            'services.fonnte.driver' => 'fake',
        ]);

        $this->assertInstanceOf(FakeMidtransPaymentGateway::class, app(MidtransPaymentGateway::class));
        $this->assertInstanceOf(FakeFonnteNotificationClient::class, app(FonnteNotificationClient::class));
    }

    public function test_midtrans_callback_signature_can_be_verified_without_real_api_call(): void
    {
        config(['services.midtrans.server_key' => 'server-key']);

        $payload = [
            'order_id' => 'SOFA-001-1',
            'status_code' => '200',
            'gross_amount' => '2500000.00',
        ];

        $payload['signature_key'] = hash('sha512', 'SOFA-001-1'.'200'.'2500000.00'.'server-key');

        $gateway = new HttpMidtransPaymentGateway(new HttpFactory());

        $this->assertTrue($gateway->verifyCallbackSignature($payload));
        $this->assertFalse($gateway->verifyCallbackSignature([...$payload, 'signature_key' => 'invalid']));
    }

    public function test_midtrans_status_mapping_matches_prd_values(): void
    {
        $gateway = new FakeMidtransPaymentGateway();

        $this->assertSame('success', $gateway->mapTransactionStatus('settlement'));
        $this->assertSame('success', $gateway->mapTransactionStatus('capture'));
        $this->assertSame('pending', $gateway->mapTransactionStatus('pending'));
        $this->assertSame('expired', $gateway->mapTransactionStatus('expire'));
        $this->assertSame('cancelled', $gateway->mapTransactionStatus('cancel'));
        $this->assertSame('failed', $gateway->mapTransactionStatus('deny'));
        $this->assertSame('failed', $gateway->mapTransactionStatus('failure'));
    }

}
