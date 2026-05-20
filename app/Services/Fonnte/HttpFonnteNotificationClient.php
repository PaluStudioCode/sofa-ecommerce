<?php

namespace App\Services\Fonnte;

use Illuminate\Http\Client\Factory as HttpFactory;
use RuntimeException;

class HttpFonnteNotificationClient implements FonnteNotificationClient
{
    public function __construct(private readonly HttpFactory $http)
    {
    }

    public function sendWhatsApp(string $target, string $message, array $options = []): array
    {
        $token = (string) config('services.fonnte.token', '');

        if ($token === '') {
            throw new RuntimeException('Fonnte token is not configured.');
        }

        return $this->http
            ->withHeader('Authorization', $token)
            ->asForm()
            ->post(rtrim((string) config('services.fonnte.base_url'), '/').'/send', [
                'target' => $target,
                'message' => $message,
                ...$options,
            ])
            ->throw()
            ->json();
    }
}
