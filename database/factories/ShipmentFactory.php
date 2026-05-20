<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\Shipment;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Shipment> */
class ShipmentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'order_id' => Order::factory(),
            'status' => 'belum_dijadwalkan',
            'scheduled_at' => null,
            'delivered_at' => null,
            'driver_name' => null,
            'driver_phone' => null,
            'vehicle_note' => null,
            'shipping_note' => null,
        ];
    }
}
