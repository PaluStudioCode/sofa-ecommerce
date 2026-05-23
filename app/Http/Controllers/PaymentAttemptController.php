<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Services\Payments\PaymentAttemptService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class PaymentAttemptController extends Controller
{
    public function store(Request $request, Order $order, PaymentAttemptService $payments): RedirectResponse
    {
        abort_unless($order->user_id === $request->user()->id, 404);

        $request->validate([
            'return_to' => ['nullable', 'in:order'],
        ]);

        $payments->createAttempt($order);

        return redirect()
            ->route('orders.show', ['order' => $order->id, 'payment_attempt' => 1])
            ->with('success', 'Pembayaran baru dibuat.');
    }
}
