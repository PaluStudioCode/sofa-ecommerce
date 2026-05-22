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

        $data = $request->validate([
            'return_to' => ['nullable', 'in:checkout,order'],
        ]);

        $payments->createAttempt($order);

        $route = ($data['return_to'] ?? 'checkout') === 'order'
            ? 'orders.show'
            : 'checkout.index';

        return redirect()
            ->route($route, ['order' => $order->id])
            ->with('success', 'Payment attempt baru dibuat.');
    }
}
