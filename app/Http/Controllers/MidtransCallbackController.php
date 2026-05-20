<?php

namespace App\Http\Controllers;

use App\Services\Payments\PaymentAttemptService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class MidtransCallbackController extends Controller
{
    public function __invoke(Request $request, PaymentAttemptService $payments): JsonResponse
    {
        try {
            $payment = $payments->handleCallback($request->all());
        } catch (ValidationException $exception) {
            return response()->json([
                'message' => 'Callback ditolak.',
                'errors' => $exception->errors(),
            ], $this->statusFor($exception));
        }

        return response()->json([
            'message' => 'Callback diproses.',
            'payment_id' => $payment->id,
            'status' => $payment->status,
        ]);
    }

    private function statusFor(ValidationException $exception): int
    {
        if ($exception->errors()['signature_key'] ?? false) {
            return 403;
        }

        if ($exception->errors()['order_id'] ?? false) {
            return 404;
        }

        return 422;
    }
}
