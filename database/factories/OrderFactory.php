<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class OrderFactory extends Factory
{
    protected $model = Order::class;

    public function definition(): array
    {
        $subtotal = fake()->randomFloat(2, 50000, 500000);

        return [
            'user_id' => User::factory(),
            'order_number' => 'INV/' . now()->format('Ymd') . '/' . str_pad(fake()->unique()->numberBetween(1, 99999), 5, '0', STR_PAD_LEFT),
            'status' => Order::STATUS_PENDING,
            'subtotal' => $subtotal,
            'shipping_cost' => 15000,
            'point_redeemed' => 0,
            'point_earned' => 0,
            'total' => $subtotal + 15000,
            'buyer_name' => fake()->name(),
            'buyer_email' => fake()->email(),
            'buyer_phone' => fake()->phoneNumber(),
            'shipping_address' => fake()->address(),
            'shipping_note' => null,
            'note' => null,
            'paid_at' => null,
        ];
    }
}
