<?php

namespace App\Services\Notifications;

use App\Models\Notification;
use App\Models\Order;
use App\Services\Fonnte\FonnteNotificationClient;
use Throwable;

class WhatsAppNotificationService
{
    private const EVENTS = [
        'order_created' => 'Pesanan {order_number} berhasil dibuat. Total pembayaran {total}. Silakan lanjutkan pembayaran.',
        'payment_success' => 'Pembayaran pesanan {order_number} sudah berhasil diterima. Tim kami akan memproses pesanan Anda.',
        'order_processing' => 'Pesanan {order_number} sedang diproses oleh tim toko.',
        'order_shipped' => 'Pesanan {order_number} sedang dikirim oleh pengiriman internal toko.',
        'order_completed' => 'Pesanan {order_number} sudah selesai. Terima kasih sudah berbelanja di toko kami.',
        'shipment_unscheduled' => 'Pengiriman pesanan {order_number} belum dijadwalkan. Tim kami akan mengabari setelah jadwal pengiriman tersedia.',
        'shipment_scheduled' => 'Pengiriman pesanan {order_number} sudah dijadwalkan pada {scheduled_at}.',
        'shipment_in_transit' => 'Pengiriman pesanan {order_number} sedang dalam perjalanan. Petugas: {driver_name}{driver_phone}.',
        'shipment_delivered' => 'Pesanan {order_number} sudah terkirim pada {delivered_at}. Terima kasih sudah berbelanja di toko kami.',
        'shipment_failed' => 'Pengiriman pesanan {order_number} belum berhasil. Tim kami akan menghubungi Anda atau menjadwalkan ulang pengiriman.',
    ];

    public function __construct(private readonly FonnteNotificationClient $client)
    {
    }

    public function sendOrderEvent(Order $order, string $eventType): ?Notification
    {
        if (! array_key_exists($eventType, self::EVENTS) || blank($order->customer_phone)) {
            return null;
        }

        $existing = Notification::query()
            ->where('order_id', $order->id)
            ->where('channel', 'whatsapp')
            ->where('event_type', $eventType)
            ->first();

        if ($existing) {
            return $existing;
        }

        $message = $this->message($order, $eventType);

        $notification = Notification::create([
            'user_id' => $order->user_id,
            'order_id' => $order->id,
            'channel' => 'whatsapp',
            'event_type' => $eventType,
            'recipient' => $order->customer_phone,
            'message' => $message,
            'status' => 'pending',
            'provider' => 'fonnte',
        ]);

        try {
            $response = $this->client->sendWhatsApp($order->customer_phone, $message, [
                'event' => $eventType,
                'order_number' => $order->order_number,
            ]);

            $notification->update([
                'status' => ($response['status'] ?? false) ? 'sent' : 'failed',
                'provider_response' => $this->limitedResponse($response),
                'sent_at' => ($response['status'] ?? false) ? now() : null,
            ]);
        } catch (Throwable $exception) {
            $notification->update([
                'status' => 'failed',
                'provider_response' => [
                    'error' => $exception->getMessage(),
                ],
            ]);
        }

        return $notification->fresh();
    }

    public function sendForOrderStatus(Order $order, string $status): ?Notification
    {
        return match ($status) {
            'diproses' => $this->sendOrderEvent($order, 'order_processing'),
            'dikirim' => $this->sendOrderEvent($order, 'order_shipped'),
            'selesai' => $this->sendOrderEvent($order, 'order_completed'),
            default => null,
        };
    }

    public function sendForShipmentStatus(Order $order, string $status): ?Notification
    {
        return match ($status) {
            'belum_dijadwalkan' => $this->sendOrderEvent($order, 'shipment_unscheduled'),
            'dijadwalkan' => $this->sendOrderEvent($order, 'shipment_scheduled'),
            'dalam_pengiriman' => $this->sendOrderEvent($order, 'shipment_in_transit'),
            'terkirim' => $this->sendOrderEvent($order, 'shipment_delivered'),
            'gagal_dikirim' => $this->sendOrderEvent($order, 'shipment_failed'),
            default => null,
        };
    }

    private function message(Order $order, string $eventType): string
    {
        return strtr(self::EVENTS[$eventType], [
            '{order_number}' => $order->order_number,
            '{total}' => 'Rp '.number_format((float) $order->total_amount, 0, ',', '.'),
            '{scheduled_at}' => $order->shipment?->scheduled_at?->translatedFormat('d F Y H.i') ?? 'jadwal yang ditentukan admin',
            '{delivered_at}' => $order->shipment?->delivered_at?->translatedFormat('d F Y H.i') ?? 'waktu yang tercatat',
            '{driver_name}' => $order->shipment?->driver_name ?: 'petugas toko',
            '{driver_phone}' => $order->shipment?->driver_phone ? ' ('.$order->shipment->driver_phone.')' : '',
        ]);
    }

    private function limitedResponse(array $response): array
    {
        return collect($response)
            ->only(['status', 'detail', 'target', 'message', 'options', 'error'])
            ->all();
    }
}
