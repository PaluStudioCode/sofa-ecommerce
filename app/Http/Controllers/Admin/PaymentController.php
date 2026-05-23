<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PaymentController extends Controller
{
    public function index(Request $request): RedirectResponse
    {
        $filters = $request->validate([
            'keyword' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', Rule::in(['', 'pending', 'success', 'failed', 'expired', 'cancelled'])],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
        ]);

        return redirect()->route('admin.orders.index', array_filter([
            'keyword' => $filters['keyword'] ?? null,
            'payment_status' => $filters['status'] ?? null,
            'date_from' => $filters['date_from'] ?? null,
            'date_to' => $filters['date_to'] ?? null,
        ], fn ($value) => $value !== null && $value !== ''));
    }

    public function show(Payment $payment): RedirectResponse
    {
        return $payment->order_id
            ? redirect()->route('admin.orders.show', $payment->order_id)
            : redirect()->route('admin.orders.index');
    }
}
