<?php

namespace App\Services\Fonnte;

class FakeFonnteNotificationClient implements FonnteNotificationClient
{
    public function sendWhatsApp(string $target, string $message, array $options = []): array
    {
        return [
            'status' => true,
            'detail' => 'Fake Fonnte message accepted.',
            'target' => $target,
            'message' => $message,
            'options' => $options,
        ];
    }
}
