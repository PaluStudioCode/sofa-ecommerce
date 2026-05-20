<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\User;
use App\Models\Voucher;
use App\Models\VoucherUsage;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<VoucherUsage> */
class VoucherUsageFactory extends Factory
{
    public function definition(): array
    {
        return [
            'voucher_id' => Voucher::factory(),
            'user_id' => User::factory(),
            'order_id' => Order::factory(),
            'discount_amount' => 100000,
            'used_at' => now(),
        ];
    }
}
