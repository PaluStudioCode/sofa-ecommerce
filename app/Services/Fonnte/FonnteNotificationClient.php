<?php

namespace App\Services\Fonnte;

interface FonnteNotificationClient
{
    public function sendWhatsApp(string $target, string $message, array $options = []): array;
}
