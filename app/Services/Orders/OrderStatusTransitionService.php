<?php

namespace App\Services\Orders;

use App\Models\Order;
use App\Models\ProductVariant;
use App\Services\Notifications\WhatsAppNotificationService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OrderStatusTransitionService
{
    public const STATUSES = [
        'menunggu_pembayaran',
        'dibayar',
        'perlu_review_admin',
        'diproses',
        'dikirim',
        'selesai',
        'dibatalkan',
    ];

    private const ADMIN_TRANSITIONS = [
        'menunggu_pembayaran' => ['dibatalkan'],
        'dibayar' => ['diproses', 'dibatalkan'],
        'perlu_review_admin' => ['diproses', 'dibatalkan'],
        'diproses' => ['dikirim', 'dibatalkan'],
        'dikirim' => ['selesai', 'diproses', 'dibatalkan'],
        'selesai' => [],
        'dibatalkan' => [],
    ];

    public function __construct(private readonly WhatsAppNotificationService $notifications)
    {
    }

    public function updateByAdmin(Order $order, string $nextStatus): Order
    {
        return DB::transaction(function () use ($order, $nextStatus) {
            /** @var Order $lockedOrder */
            $lockedOrder = Order::query()
                ->with('items.variant')
                ->lockForUpdate()
                ->findOrFail($order->id);

            $this->assertCanTransition($lockedOrder, $nextStatus);

            if ($nextStatus === 'dibatalkan' && $lockedOrder->payment_status !== 'success') {
                $this->releaseReservedStock($lockedOrder);
            }

            $lockedOrder->update(['order_status' => $nextStatus]);
            $this->notifications->sendForOrderStatus($lockedOrder->fresh(), $nextStatus);

            return $lockedOrder->fresh();
        });
    }

    private function assertCanTransition(Order $order, string $nextStatus): void
    {
        if ($order->order_status === $nextStatus) {
            return;
        }

        if (in_array($nextStatus, self::ADMIN_TRANSITIONS[$order->order_status] ?? [], true)) {
            return;
        }

        throw ValidationException::withMessages([
            'order_status' => "Status pesanan tidak dapat berubah dari {$order->order_status} ke {$nextStatus}.",
        ]);
    }

    private function releaseReservedStock(Order $order): void
    {
        foreach ($order->items as $item) {
            if (! $item->product_variant_id) {
                continue;
            }

            /** @var ProductVariant $variant */
            $variant = ProductVariant::query()->lockForUpdate()->findOrFail($item->product_variant_id);
            $releaseQuantity = min($item->quantity, $variant->reserved_stock);

            if ($releaseQuantity > 0) {
                $variant->decrement('reserved_stock', $releaseQuantity);
            }
        }
    }
}
