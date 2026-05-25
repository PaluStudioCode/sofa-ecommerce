<?php

namespace App\Services\Notifications;

use App\Models\Order;
use App\Services\Fonnte\FonnteNotificationClient;
use Throwable;

class WhatsAppNotificationService
{
    private const EVENTS = [
        'order_created' => 'Pesanan {order_number} berhasil dibuat. Total pembayaran {total}. Silakan lanjutkan pembayaran.',
        'payment_success' => 'Pembayaran pesanan {order_number} sudah berhasil diterima. Tim kami akan memproses pesanan Anda.',
        'order_processing' => 'Pesanan {order_number} sedang diproses oleh tim toko.',
        'order_shipped' => 'Pesanan {order_number} sedang dalam perjalanan bersama pengiriman internal toko.',
        'order_completed' => 'Barang pesanan {order_number} sudah diterima. Terima kasih sudah berbelanja di toko kami.',
    ];

    public function __construct(private readonly FonnteNotificationClient $client) {}

    public function sendOrderEvent(Order $order, string $eventType): bool
    {
        $order->loadMissing(['user:id,name,phone', 'address', 'total', 'delivery']);
        $phone = $this->customerPhone($order);

        if (! array_key_exists($eventType, self::EVENTS) || blank($phone)) {
            return false;
        }

        $message = $this->message($order, $eventType);

        try {
            $this->client->sendWhatsApp($phone, $message, [
                'event' => $eventType,
                'order_number' => $order->order_number,
            ]);

            return true;
        } catch (Throwable $exception) {
            report($exception);
        }

        return false;
    }

    public function sendForOrderStatus(Order $order, string $status): bool
    {
        return match ($status) {
            'diproses' => $this->sendOrderEvent($order, 'order_processing'),
            'dalam_perjalanan' => $this->sendOrderEvent($order, 'order_shipped'),
            'barang_diterima' => $this->sendOrderEvent($order, 'order_completed'),
            default => false,
        };
    }

    private function message(Order $order, string $eventType): string
    {
        return strtr(self::EVENTS[$eventType], [
            '{order_number}' => $order->order_number,
            '{total}' => 'Rp '.number_format((float) $order->total_amount, 0, ',', '.'),
            '{scheduled_at}' => $order->delivery_scheduled_at?->translatedFormat('d F Y') ?? 'jadwal yang ditentukan admin',
            '{delivered_at}' => $order->delivery_delivered_at?->translatedFormat('d F Y') ?? 'tanggal yang tercatat',
            '{driver_name}' => $order->driver_name ?: 'petugas toko',
            '{driver_phone}' => $order->driver_phone ? ' ('.$order->driver_phone.')' : '',
        ]);
    }

    private function customerPhone(Order $order): ?string
    {
        return $order->address?->phone ?: $order->user?->phone;
    }
}
