<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Kwitansi - {{ $order->order_number }}</title>
    <style>
        body { font-family: sans-serif; font-size: 14px; color: #333; line-height: 1.5; }
        .header { text-align: center; margin-bottom: 30px; }
        .header h1 { margin: 0; font-size: 24px; text-transform: uppercase; }
        .details { width: 100%; margin-bottom: 30px; }
        .details td { vertical-align: top; }
        .table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        .table th, .table td { padding: 10px; border-bottom: 1px solid #ddd; text-align: left; }
        .table th { background-color: #f9f9f9; }
        .table .text-right { text-align: right; }
        .total-row td { font-weight: bold; border-top: 2px solid #333; }
        .footer { text-align: center; margin-top: 50px; font-size: 12px; color: #777; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Kwitansi Pembelian</h1>
        <p>No. Pesanan: {{ $order->order_number }}</p>
    </div>
    
    <table class="details">
        <tr>
            <td>
                <strong>Penerima:</strong><br>
                {{ $order->address->recipient_name ?? $order->user->name }}<br>
                {{ $order->address->phone ?? $order->user->phone }}<br>
                {{ $order->address->formatted_address ?? '-' }}<br>
                {{ $order->address->city ?? '' }}, {{ $order->address->district ?? '' }}
            </td>
            <td style="text-align: right;">
                <strong>Tanggal Pesanan:</strong><br>
                {{ $order->created_at->format('d M Y, H:i') }}<br><br>
                <strong>Status Pembayaran:</strong><br>
                {{ strtoupper($order->payment_status) }}
            </td>
        </tr>
    </table>

    <table class="table">
        <thead>
            <tr>
                <th>Item</th>
                <th>Harga</th>
                <th>Qty</th>
                <th class="text-right">Subtotal</th>
            </tr>
        </thead>
        <tbody>
            @foreach($order->items as $item)
            <tr>
                <td>
                    {{ $item->product_name }}<br>
                    <small style="color: #666;">{{ $item->variant_name ?? 'Varian Standar' }}</small>
                </td>
                <td>Rp {{ number_format($item->product_price, 0, ',', '.') }}</td>
                <td>{{ $item->quantity }}</td>
                <td class="text-right">Rp {{ number_format($item->subtotal, 0, ',', '.') }}</td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td colspan="3" class="text-right">Subtotal Produk</td>
                <td class="text-right">Rp {{ number_format($order->subtotal_amount, 0, ',', '.') }}</td>
            </tr>
            @if($order->discount_amount > 0)
            <tr>
                <td colspan="3" class="text-right">Diskon</td>
                <td class="text-right">-Rp {{ number_format($order->discount_amount, 0, ',', '.') }}</td>
            </tr>
            @endif
            <tr>
                <td colspan="3" class="text-right">Ongkos Kirim</td>
                <td class="text-right">Rp {{ number_format($order->shipping_cost, 0, ',', '.') }}</td>
            </tr>
            <tr class="total-row">
                <td colspan="3" class="text-right">Total Keseluruhan</td>
                <td class="text-right">Rp {{ number_format($order->total_amount, 0, ',', '.') }}</td>
            </tr>
        </tfoot>
    </table>

    <div class="footer">
        <p>Terima kasih atas pesanan Anda.</p>
    </div>
</body>
</html>
