<?php

namespace Tests\Feature\Api\Order;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Shipment;
use App\Models\ShipmentTrackingLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderDetailTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_get_order_detail(): void
    {
        $user = User::factory()->create();
        $order = Order::factory()->create(['user_id' => $user->id]);
        OrderItem::factory(2)->create(['order_id' => $order->id]);
        Payment::factory()->create(['order_id' => $order->id]);
        $shipment = Shipment::factory()->create(['order_id' => $order->id]);
        ShipmentTrackingLog::factory(2)->create(['shipment_id' => $shipment->id]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson("/api/orders/{$order->order_number}");

        $response->assertOk()
            ->assertJsonStructure([
                'success', 'message', 'data' => [
                    'id', 'order_number', 'status', 'status_label',
                    'buyer_name', 'buyer_email', 'buyer_phone',
                    'shipping_address', 'subtotal', 'shipping_cost', 'total',
                    'items', 'payments', 'shipment',
                ],
            ]);
    }

    public function test_order_detail_contains_snapshot_data(): void
    {
        $user = User::factory()->create();
        $order = Order::factory()->create(['user_id' => $user->id]);
        OrderItem::factory()->create([
            'order_id' => $order->id,
            'product_name' => 'Test Product',
            'variant_label' => 'Red',
            'unit_price' => 50000,
            'quantity' => 2,
            'subtotal' => 100000,
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson("/api/orders/{$order->order_number}");

        $response->assertOk();
        $item = $response->json('data.items.0');
        $this->assertEquals('Test Product', $item['product_name']);
        $this->assertEquals('Red', $item['variant_label']);
        $this->assertEquals(50000, (float) $item['unit_price']);
        $this->assertEquals(2, $item['quantity']);
        $this->assertEquals(100000, (float) $item['subtotal']);
    }

    public function test_cannot_access_other_users_order_detail(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();
        $order = Order::factory()->create(['user_id' => $userA->id]);

        $response = $this->actingAs($userB, 'sanctum')
            ->getJson("/api/orders/{$order->order_number}");

        $response->assertNotFound();
    }

    public function test_order_detail_404_for_non_existent_number(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/orders/NONEXISTENT');

        $response->assertNotFound();
    }

    public function test_order_detail_does_not_include_internal_note(): void
    {
        $user = User::factory()->create();
        $order = Order::factory()->create([
            'user_id' => $user->id,
            'note' => 'Internal admin note',
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson("/api/orders/{$order->order_number}");

        $response->assertOk();
        $response->assertJsonMissingPath('data.note');
    }
}
