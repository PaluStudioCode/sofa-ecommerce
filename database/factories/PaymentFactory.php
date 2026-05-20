<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\Payment;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Payment> */
class PaymentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'order_id' => Order::factory(),
            'attempt_number' => 1,
            'midtrans_order_id' => 'MT-'.fake()->unique()->numerify('########'),
            'midtrans_transaction_id' => null,
            'payment_type' => null,
            'status' => 'pending',
            'transaction_status' => 'pending',
            'fraud_status' => null,
            'gross_amount' => 1000000,
            'snap_token' => 'fake-snap-token-'.fake()->unique()->bothify('??###'),
            'redirect_url' => null,
            'paid_at' => null,
            'expired_at' => now()->addDay(),
            'raw_response' => null,
        ];
    }

    public function success(): static
    {
        return $this->state(fn () => [
            'status' => 'success',
            'transaction_status' => 'settlement',
            'midtrans_transaction_id' => 'TRX-'.fake()->unique()->numerify('########'),
            'payment_type' => 'bank_transfer',
            'paid_at' => now(),
        ]);
    }
}
