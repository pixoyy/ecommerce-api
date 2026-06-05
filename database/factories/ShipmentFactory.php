<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\Shipment;
use Illuminate\Database\Eloquent\Factories\Factory;

class ShipmentFactory extends Factory
{
    protected $model = Shipment::class;

    public function definition(): array
    {
        return [
            'order_id' => Order::factory(),
            'warehouse_id' => null,
            'shipping_cost' => 15000,
            'status' => 1,
            'courier_name' => fake()->randomElement(['JNE', 'J&T', 'Sicepat', 'Gojek']),
            'tracking_number' => fake()->bothify('#############'),
            'delivered_at' => null,
        ];
    }
}
