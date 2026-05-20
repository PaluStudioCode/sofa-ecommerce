<?php

namespace App\Services\Midtrans;

interface MidtransPaymentGateway
{
    public function createSnapTransaction(array $payload): array;

    public function verifyCallbackSignature(array $payload): bool;

    public function mapTransactionStatus(?string $transactionStatus, ?string $fraudStatus = null): string;

    public function callbackUrl(): ?string;

    public function clientConfig(): array;
}
