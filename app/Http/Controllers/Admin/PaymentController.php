<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Support\Navigation\DashboardNavigation;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class PaymentController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = $request->validate([
            'keyword' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', Rule::in(['', 'pending', 'success', 'failed', 'expired', 'cancelled'])],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
        ]);

        $payments = Payment::query()
            ->with('order:id,order_number,customer_name,customer_phone,total_amount,order_status,payment_status')
            ->when($filters['keyword'] ?? null, function ($query, string $keyword) {
                $query->where(function ($query) use ($keyword) {
                    $query->where('midtrans_order_id', 'like', "%{$keyword}%")
                        ->orWhere('midtrans_transaction_id', 'like', "%{$keyword}%")
                        ->orWhereHas('order', fn ($query) => $query
                            ->where('order_number', 'like', "%{$keyword}%")
                            ->orWhere('customer_name', 'like', "%{$keyword}%"));
                });
            })
            ->when($filters['status'] ?? null, fn ($query, string $status) => $query->where('status', $status))
            ->when($filters['date_from'] ?? null, fn ($query, string $date) => $query->whereDate('created_at', '>=', $date))
            ->when($filters['date_to'] ?? null, fn ($query, string $date) => $query->whereDate('created_at', '<=', $date))
            ->latest()
            ->paginate(12)
            ->withQueryString()
            ->through(fn (Payment $payment) => $this->payload($payment));

        return Inertia::render('Admin/Payments/Index', [
            'navigationGroups' => DashboardNavigation::forUser($request->user()),
            'breadcrumbs' => [
                ['label' => 'Dashboard', 'href' => route('dashboard')],
                ['label' => 'Pembayaran', 'href' => route('admin.payments.index')],
            ],
            'payments' => $payments,
            'filters' => [
                'keyword' => $filters['keyword'] ?? '',
                'status' => $filters['status'] ?? '',
                'date_from' => $filters['date_from'] ?? '',
                'date_to' => $filters['date_to'] ?? '',
            ],
            'statusOptions' => $this->statusOptions(true),
        ]);
    }

    public function show(Request $request, Payment $payment): Response
    {
        $payment->load('order.user:id,name,email');

        return Inertia::render('Admin/Payments/Show', [
            'navigationGroups' => DashboardNavigation::forUser($request->user()),
            'breadcrumbs' => [
                ['label' => 'Dashboard', 'href' => route('dashboard')],
                ['label' => 'Pembayaran', 'href' => route('admin.payments.index')],
                ['label' => '#'.$payment->attempt_number, 'href' => route('admin.payments.show', $payment)],
            ],
            'payment' => [
                ...$this->payload($payment, true),
                'raw_response_preview' => $this->limitedRawResponse($payment->raw_response),
            ],
        ]);
    }

    private function payload(Payment $payment, bool $includeOrderDetails = false): array
    {
        $order = $payment->order;

        return [
            'id' => $payment->id,
            'attempt_number' => $payment->attempt_number,
            'midtrans_order_id' => $payment->midtrans_order_id,
            'midtrans_transaction_id' => $payment->midtrans_transaction_id,
            'payment_type' => $payment->payment_type,
            'status' => $payment->status,
            'transaction_status' => $payment->transaction_status,
            'fraud_status' => $payment->fraud_status,
            'gross_amount' => (float) $payment->gross_amount,
            'paid_at' => $payment->paid_at?->toIso8601String(),
            'expired_at' => $payment->expired_at?->toIso8601String(),
            'created_at' => $payment->created_at?->toIso8601String(),
            'order' => $order ? [
                'id' => $order->id,
                'order_number' => $order->order_number,
                'customer_name' => $order->customer_name,
                'customer_phone' => $includeOrderDetails ? $order->customer_phone : null,
                'customer_email' => $includeOrderDetails ? $order->user?->email : null,
                'total_amount' => (float) $order->total_amount,
                'order_status' => $order->order_status,
                'payment_status' => $order->payment_status,
            ] : null,
        ];
    }

    private function limitedRawResponse(?array $raw): ?array
    {
        if (! $raw) {
            return null;
        }

        return collect($raw)
            ->only([
                'transaction_status',
                'fraud_status',
                'status_code',
                'status_message',
                'payment_type',
                'transaction_time',
                'order_id',
                'gross_amount',
            ])
            ->all();
    }

    private function statusOptions(bool $withAll): array
    {
        $options = collect(['pending', 'success', 'failed', 'expired', 'cancelled'])
            ->map(fn (string $status) => ['value' => $status, 'label' => str_replace('_', ' ', $status)]);

        return $withAll ? $options->prepend(['value' => '', 'label' => 'Semua status'])->values()->all() : $options->values()->all();
    }
}
