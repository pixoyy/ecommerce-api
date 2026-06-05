<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\Payment;
use Illuminate\Database\Eloquent\Factories\Factory;

class PaymentFactory extends Factory
{
    protected $model = Payment::class;

    public function definition(): array
    {
        return [
            'order_id' => Order::factory(),
            'payment_account_id' => null,
            'amount' => fake()->randomFloat(2, 50000, 500000),
            'status' => 1,
            'proof_path' => null,
            'rejected_reason' => null,
            'approved_at' => null,
            'rejected_at' => null,
        ];
    }
}
