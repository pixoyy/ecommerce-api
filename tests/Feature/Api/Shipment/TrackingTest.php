<?php

namespace Tests\Feature\Api\Shipment;

use App\Models\Order;
use App\Models\Shipment;
use App\Models\ShipmentTrackingLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TrackingTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_get_tracking_for_order_with_shipment(): void
    {
        $user = User::factory()->create();
        $order = Order::factory()->create(['user_id' => $user->id]);
        $shipment = Shipment::factory()->create(['order_id' => $order->id]);
        ShipmentTrackingLog::factory(2)->create(['shipment_id' => $shipment->id]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson("/api/orders/{$order->id}/tracking");

        $response->assertOk()
            ->assertJsonStructure([
                'success', 'message', 'data' => [
                    'shipment', 'tracking_logs',
                ],
            ]);

        $this->assertNotNull($response->json('data.shipment'));
        $this->assertCount(2, $response->json('data.tracking_logs'));
    }

    public function test_tracking_empty_when_no_shipment(): void
    {
        $user = User::factory()->create();
        $order = Order::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson("/api/orders/{$order->id}/tracking");

        $response->assertOk();

        $this->assertNull($response->json('data.shipment'));
        $this->assertEmpty($response->json('data.tracking_logs'));
    }

    public function test_cannot_access_other_users_tracking(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();
        $order = Order::factory()->create(['user_id' => $userA->id]);

        $response = $this->actingAs($userB, 'sanctum')
            ->getJson("/api/orders/{$order->id}/tracking");

        $response->assertNotFound();
    }

    public function test_tracking_logs_ordered_asc(): void
    {
        $user = User::factory()->create();
        $order = Order::factory()->create(['user_id' => $user->id]);
        $shipment = Shipment::factory()->create(['order_id' => $order->id]);

        $log1 = ShipmentTrackingLog::factory()->create([
            'shipment_id' => $shipment->id,
            'created_at' => now()->subDays(2),
            'status' => 1,
        ]);
        $log2 = ShipmentTrackingLog::factory()->create([
            'shipment_id' => $shipment->id,
            'created_at' => now()->subDay(),
            'status' => 2,
        ]);
        $log3 = ShipmentTrackingLog::factory()->create([
            'shipment_id' => $shipment->id,
            'created_at' => now(),
            'status' => 3,
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson("/api/orders/{$order->id}/tracking");

        $response->assertOk();
        $logs = $response->json('data.tracking_logs');

        $this->assertEquals($log1->id, $logs[0]['id']);
        $this->assertEquals($log2->id, $logs[1]['id']);
        $this->assertEquals($log3->id, $logs[2]['id']);
    }
}
