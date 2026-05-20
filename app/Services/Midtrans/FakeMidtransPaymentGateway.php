<?php

namespace App\Services\Midtrans;

use Illuminate\Support\Arr;

class FakeMidtransPaymentGateway implements MidtransPaymentGateway
{
    public function createSnapTransaction(array $payload): array
    {
        $orderId = (string) Arr::get($payload, 'transaction_details.order_id', 'fake-order');

        return [
            'token' => 'fake-snap-token-'.$orderId,
            'redirect_url' => 'https://app.sandbox.midtrans.com/snap/v2/vtweb/fake-snap-token-'.$orderId,
            'order_id' => $orderId,
        ];
    }

    public function verifyCallbackSignature(array $payload): bool
    {
        return (bool) Arr::get($payload, 'signature_valid', true);
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
            'clientKey' => config('services.midtrans.client_key') ?: 'fake-client-key',
            'isProduction' => false,
            'snapBaseUrl' => config('services.midtrans.snap_base_url'),
        ];
    }
}
