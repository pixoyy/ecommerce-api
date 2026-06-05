<?php

namespace Tests\Feature\Api\Order;

use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderHistoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_list_orders(): void
    {
        $user = User::factory()->create();
        Order::factory(3)->create(['user_id' => $user->id]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/orders');

        $response->assertOk()
            ->assertJsonStructure([
                'success', 'message', 'data' => [
                    'data', 'current_page', 'last_page', 'per_page', 'total',
                ],
            ]);

        $this->assertCount(3, $response->json('data.data'));
    }

    public function test_can_filter_orders_by_status(): void
    {
        $user = User::factory()->create();
        Order::factory()->create(['user_id' => $user->id, 'status' => Order::STATUS_PENDING]);
        Order::factory()->create(['user_id' => $user->id, 'status' => Order::STATUS_PROCESSING]);
        Order::factory()->create(['user_id' => $user->id, 'status' => Order::STATUS_SHIPPED]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/orders?status=' . Order::STATUS_PENDING);

        $response->assertOk();
        $orders = $response->json('data.data');
        $this->assertCount(1, $orders);
        $this->assertEquals(Order::STATUS_PENDING, $orders[0]['status']);
    }

    public function test_can_sort_orders(): void
    {
        $user = User::factory()->create();
        Order::factory()->create(['user_id' => $user->id, 'total' => 50000]);
        Order::factory()->create(['user_id' => $user->id, 'total' => 200000]);
        Order::factory()->create(['user_id' => $user->id, 'total' => 100000]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/orders?sort=total&order=desc');

        $response->assertOk();
        $orders = $response->json('data.data');
        $this->assertEquals(200000, (float) $orders[0]['total']);
        $this->assertEquals(100000, (float) $orders[1]['total']);
        $this->assertEquals(50000, (float) $orders[2]['total']);
    }

    public function test_cannot_list_other_users_orders(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();

        Order::factory(2)->create(['user_id' => $userA->id]);

        $response = $this->actingAs($userB, 'sanctum')
            ->getJson('/api/orders');

        $response->assertOk();
        $this->assertCount(0, $response->json('data.data'));
    }
}
