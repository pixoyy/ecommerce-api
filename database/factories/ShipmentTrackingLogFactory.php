<?php

namespace Database\Factories;

use App\Models\Shipment;
use App\Models\ShipmentTrackingLog;
use Illuminate\Database\Eloquent\Factories\Factory;

class ShipmentTrackingLogFactory extends Factory
{
    protected $model = ShipmentTrackingLog::class;

    public function definition(): array
    {
        return [
            'shipment_id' => Shipment::factory(),
            'updated_by' => null,
            'status' => 1,
            'note' => fake()->sentence(),
            'location' => fake()->city(),
        ];
    }
}
