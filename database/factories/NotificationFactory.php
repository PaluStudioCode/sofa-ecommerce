<?php

namespace Database\Factories;

use App\Models\Notification;
use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Notification> */
class NotificationFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'order_id' => Order::factory(),
            'channel' => 'whatsapp',
            'recipient' => fake()->phoneNumber(),
            'message' => fake()->sentence(),
            'status' => 'pending',
            'provider' => 'fonnte',
            'provider_response' => null,
            'sent_at' => null,
        ];
    }

    public function sent(): static
    {
        return $this->state(fn () => [
            'status' => 'sent',
            'provider_response' => ['status' => true],
            'sent_at' => now(),
        ]);
    }
}
