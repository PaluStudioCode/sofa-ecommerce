<?php

namespace App\Exports;

use App\Models\Order;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class OrdersExport implements FromQuery, WithHeadings, WithMapping, WithEvents
{
    use Exportable;

    protected $filters;

    public function __construct(array $filters)
    {
        $this->filters = $filters;
    }

    public function query()
    {
        return Order::query()
            ->with(['user', 'address', 'payments'])
            ->when($this->filters['keyword'] ?? null, function (Builder $query, string $keyword) {
                $query->where('order_number', 'like', "%{$keyword}%")
                    ->orWhereHas('user', fn ($q) => $q->where('name', 'like', "%{$keyword}%"))
                    ->orWhereHas('address', fn ($q) => $q->where('recipient_name', 'like', "%{$keyword}%"));
            })
            ->when($this->filters['order_status'] ?? null, fn ($query, string $status) => $query->where('order_status', $status))
            ->when($this->filters['payment_status'] ?? null, fn ($query, string $status) => $query->whereHas('payments', fn ($q) => $q->where('status', $status)))
            ->when($this->filters['date_from'] ?? null, fn ($query, string $date) => $query->whereDate('created_at', '>=', $date))
            ->when($this->filters['date_to'] ?? null, fn ($query, string $date) => $query->whereDate('created_at', '<=', $date))
            ->latest();
    }

    public function headings(): array
    {
        return [
            'Nomor Order',
            'Tanggal Dibuat',
            'Nama Pembeli',
            'No. Telp',
            'Email',
            'Status Pembayaran',
            'Status Pesanan',
            'Total Belanja (Rp)',
        ];
    }

    public function map($order): array
    {
        return [
            $order->order_number,
            $order->created_at->format('Y-m-d H:i:s'),
            $order->address?->recipient_name ?? $order->user?->name,
            $order->address?->phone ?? $order->user?->phone,
            $order->user?->email,
            strtoupper($order->payment_status),
            strtoupper($order->order_status),
            (float) $order->total_amount,
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $highestRow = $sheet->getHighestRow();
                $nextRow = $highestRow + 1;

                // Tambahkan teks TOTAL KESELURUHAN dan gabungkan sel (Merge) A sampai G
                $sheet->setCellValue('A' . $nextRow, 'TOTAL KESELURUHAN');
                $sheet->mergeCells("A{$nextRow}:G{$nextRow}");
                
                // Posisikan teks rata kanan (Right Align)
                $sheet->getStyle("A{$nextRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                
                // Hitung total langsung menggunakan query SQL agar akurat (100% jalan)
                $totalSum = (float) $this->query()
                    ->join('order_totals', 'orders.id', '=', 'order_totals.order_id')
                    ->sum('order_totals.total_amount');
                $sheet->setCellValue('H' . $nextRow, $totalSum);
                
                // Buat baris total menjadi cetak tebal (Bold)
                $sheet->getStyle("A{$nextRow}:H{$nextRow}")->getFont()->setBold(true);
            },
        ];
    }
}
