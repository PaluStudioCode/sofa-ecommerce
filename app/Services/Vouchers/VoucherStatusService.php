<?php

namespace App\Services\Vouchers;

use App\Models\Voucher;
use Illuminate\Support\Carbon;

class VoucherStatusService
{
    public function syncAll(): void
    {
        Voucher::query()
            ->where('end_at', '<', now())
            ->where('status', '!=', 'kedaluwarsa')
            ->update(['status' => 'kedaluwarsa']);

        Voucher::query()
            ->whereNotNull('quota')
            ->where('status', '!=', 'kuota_habis')
            ->get()
            ->each(function (Voucher $voucher) {
                if ($this->usedCount($voucher) >= (int) $voucher->quota) {
                    $voucher->forceFill(['status' => 'kuota_habis'])->save();
                }
            });
    }

    public function normalize(array $data, int $usedCount = 0): array
    {
        if (($data['end_at'] ?? null) && now()->greaterThan(Carbon::parse($data['end_at']))) {
            $data['status'] = 'kedaluwarsa';
        }

        if (($data['quota'] ?? null) !== null && $usedCount >= (int) $data['quota']) {
            $data['status'] = 'kuota_habis';
        }

        return $data;
    }

    public function sync(Voucher $voucher): Voucher
    {
        $status = $voucher->status;

        if ($voucher->end_at->isPast()) {
            $status = 'kedaluwarsa';
        } elseif ($voucher->quota !== null && $this->usedCount($voucher) >= $voucher->quota) {
            $status = 'kuota_habis';
        }

        if ($status !== $voucher->status) {
            $voucher->forceFill(['status' => $status])->save();
            $voucher->refresh();
        }

        return $voucher;
    }

    private function usedCount(Voucher $voucher): int
    {
        return (int) $voucher->voucherSnapshots()
            ->whereHas('order.payments', fn ($query) => $query->where('status', 'success'))
            ->count();
    }
}
