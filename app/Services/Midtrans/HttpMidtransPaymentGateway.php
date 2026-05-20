<?php

namespace App\Services\Midtrans;

use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Support\Arr;
use RuntimeException;

class HttpMidtransPaymentGateway implements MidtransPaymentGateway
{
    public function __construct(private readonly HttpFactory $http)
    {
    }

    public function createSnapTransaction(array $payload): array
    {
        $serverKey = $this->serverKey();

        $response = $this->http
            ->withBasicAuth($serverKey, '')
            ->acceptJson()
            ->asJson()
            ->post(rtrim((string) config('services.midtrans.snap_base_url'), '/').'/snap/v1/transactions', $payload)
            ->throw();

        return $response->json();
    }

    public function verifyCallbackSignature(array $payload): bool
    {
        $signature = (string) Arr::get($payload, 'signature_key', '');

        if ($signature === '') {
            return false;
        }

        $expected = hash('sha512', implode('', [
            (string) Arr::get($payload, 'order_id', ''),
            (string) Arr::get($payload, 'status_code', ''),
            (string) Arr::get($payload, 'gross_amount', ''),
            $this->serverKey(),
        ]));

        return hash_equals($expected, $signature);
    }

    public function mapTransactionStatus(?string $transactionStatus, ?string $fraudStatus = null): string
    {
        return match ($transactionStatus) {
            'settlement', 'capture' => 'success',
            'pending' => 'pending',
            'expire' => 'expired',
            'cancel' => 'cancelled',
            'deny', 'failure' => 'failed',
            default => 'pending',
        };
    }

    public function callbackUrl(): ?string
    {
        return config('services.midtrans.callback_url');
    }

    public function clientConfig(): array
    {
        return [
            'clientKey' => config('services.midtrans.client_key'),
            'isProduction' => (bool) config('services.midtrans.is_production'),
            'snapBaseUrl' => config('services.midtrans.snap_base_url'),
        ];
    }

    private function serverKey(): string
    {
        $serverKey = (string) config('services.midtrans.server_key', '');

        if ($serverKey === '') {
            throw new RuntimeException('Midtrans server key is not configured.');
        }

        return $serverKey;
    }
}
