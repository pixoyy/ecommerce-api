<?php

namespace Tests\Feature\Api\Reward;

use App\Models\Order;
use App\Models\PointTransaction;
use App\Models\User;
use App\Models\UserPoint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RewardTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_get_point_balance(): void
    {
        $user = User::factory()->create();
        UserPoint::create(['user_id' => $user->id, 'balance' => 5000]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/rewards/balance');

        $response->assertOk()
            ->assertJsonPath('data.balance', 5000);
    }

    public function test_balance_returns_zero_for_new_user(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/rewards/balance');

        $response->assertOk()
            ->assertJsonPath('data.balance', 0);
    }

    public function test_can_list_point_transactions(): void
    {
        $user = User::factory()->create();
        $order = Order::factory()->create(['user_id' => $user->id]);

        PointTransaction::create([
            'user_id' => $user->id,
            'order_id' => $order->id,
            'type' => 1,
            'amount' => 1000,
            'description' => 'Poin masuk',
        ]);
        PointTransaction::create([
            'user_id' => $user->id,
            'order_id' => $order->id,
            'type' => 2,
            'amount' => 500,
            'description' => 'Poin keluar',
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/rewards/transactions');

        $response->assertOk()
            ->assertJsonStructure([
                'success', 'message', 'data' => [
                    'data', 'current_page', 'last_page', 'per_page', 'total',
                ],
            ]);

        $this->assertCount(2, $response->json('data.data'));
    }

    public function test_transaction_contains_correct_fields(): void
    {
        $user = User::factory()->create();
        $order = Order::factory()->create(['user_id' => $user->id]);

        PointTransaction::create([
            'user_id' => $user->id,
            'order_id' => $order->id,
            'type' => 1,
            'amount' => 2000,
            'description' => 'Test transaction',
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/rewards/transactions');

        $response->assertOk();
        $tx = $response->json('data.data.0');

        $this->assertArrayHasKey('id', $tx);
        $this->assertArrayHasKey('type', $tx);
        $this->assertArrayHasKey('type_label', $tx);
        $this->assertArrayHasKey('amount', $tx);
        $this->assertArrayHasKey('description', $tx);
        $this->assertArrayHasKey('order_number', $tx);
        $this->assertArrayHasKey('created_at', $tx);
        $this->assertEquals(1, $tx['type']);
        $this->assertEquals('Poin Masuk', $tx['type_label']);
        $this->assertEquals($order->order_number, $tx['order_number']);
    }

    public function test_cannot_access_other_users_transactions(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();
        $order = Order::factory()->create(['user_id' => $userA->id]);

        PointTransaction::create([
            'user_id' => $userA->id,
            'order_id' => $order->id,
            'type' => 1,
            'amount' => 1000,
            'description' => 'User A poin',
        ]);

        $response = $this->actingAs($userB, 'sanctum')
            ->getJson('/api/rewards/transactions');

        $response->assertOk();
        $this->assertEmpty($response->json('data.data'));
    }
}
