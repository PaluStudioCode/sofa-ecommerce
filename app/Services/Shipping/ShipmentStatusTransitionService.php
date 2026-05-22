<?php

namespace App\Services\Shipping;

use App\Models\Shipment;
use Illuminate\Validation\ValidationException;

class ShipmentStatusTransitionService
{
    public const STATUSES = [
        'belum_dijadwalkan',
        'dijadwalkan',
        'dalam_pengiriman',
        'terkirim',
        'gagal_dikirim',
    ];

    private const TRANSITIONS = [
        'belum_dijadwalkan' => ['dijadwalkan'],
        'dijadwalkan' => ['dalam_pengiriman', 'gagal_dikirim'],
        'dalam_pengiriman' => ['terkirim', 'gagal_dikirim'],
        'gagal_dikirim' => ['dijadwalkan'],
        'terkirim' => [],
    ];

    public function allowedStatuses(?Shipment $shipment): array
    {
        $currentStatus = $shipment?->status;

        if ($currentStatus === null) {
            return ['belum_dijadwalkan', 'dijadwalkan'];
        }

        return array_values(array_unique([
            $currentStatus,
            ...(self::TRANSITIONS[$currentStatus] ?? []),
        ]));
    }

    public function assertCanTransition(?Shipment $shipment, string $nextStatus): void
    {
        $currentStatus = $shipment?->status;

        if ($currentStatus === null) {
            if (in_array($nextStatus, ['belum_dijadwalkan', 'dijadwalkan'], true)) {
                return;
            }

            throw ValidationException::withMessages([
                'status' => 'Shipment baru harus dimulai dari belum dijadwalkan atau dijadwalkan.',
            ]);
        }

        if ($currentStatus === $nextStatus) {
            return;
        }

        if (in_array($nextStatus, self::TRANSITIONS[$currentStatus] ?? [], true)) {
            return;
        }

        throw ValidationException::withMessages([
            'status' => "Status pengiriman tidak dapat berubah dari {$currentStatus} ke {$nextStatus}.",
        ]);
    }
}
