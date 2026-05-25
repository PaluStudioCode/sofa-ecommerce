<?php

namespace App\Services\Orders;

use App\Models\Order;
use App\Services\Notifications\WhatsAppNotificationService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OrderStatusTransitionService
{
    public const STATUSES = [
        'menunggu_pembayaran',
        'diproses',
        'dalam_perjalanan',
        'barang_diterima',
    ];

    public const ADMIN_ACTION_STATUSES = [
        'dalam_perjalanan',
        'barang_diterima',
    ];

    private const ADMIN_TRANSITIONS = [
        'menunggu_pembayaran' => [],
        'diproses' => ['dalam_perjalanan'],
        'dalam_perjalanan' => ['barang_diterima'],
        'barang_diterima' => [],
    ];

    public function __construct(private readonly WhatsAppNotificationService $notifications) {}

    public function updateByAdmin(Order $order, string $nextStatus): Order
    {
        return DB::transaction(function () use ($order, $nextStatus) {
            /** @var Order $lockedOrder */
            $lockedOrder = Order::query()
                ->lockForUpdate()
                ->findOrFail($order->id);

            $this->assertCanTransition($lockedOrder, $nextStatus);

            $lockedOrder->update(['order_status' => $nextStatus]);
            $this->notifications->sendForOrderStatus($lockedOrder->fresh(), $nextStatus);

            return $lockedOrder->fresh();
        });
    }

    public function nextStatusesForAdmin(Order $order): array
    {
        return self::ADMIN_TRANSITIONS[$order->order_status] ?? [];
    }

    private function assertCanTransition(Order $order, string $nextStatus): void
    {
        if ($order->order_status === $nextStatus) {
            return;
        }

        if (in_array($nextStatus, $this->nextStatusesForAdmin($order), true)) {
            return;
        }

        throw ValidationException::withMessages([
            'order_status' => "Status pesanan tidak dapat berubah dari {$order->order_status} ke {$nextStatus}.",
        ]);
    }
}
