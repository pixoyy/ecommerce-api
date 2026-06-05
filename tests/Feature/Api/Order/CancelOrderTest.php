<?php

namespace Tests\Feature\Api\Order;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use App\Models\UserPoint;
use App\Models\Warehouse;
use App\Models\WarehouseStock;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CancelOrderTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_cancel_pending_order(): void
    {
        $user = User::factory()->create();
        $order = Order::factory()->create([
            'user_id' => $user->id,
            'status' => Order::STATUS_PENDING,
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson("/api/orders/{$order->id}/cancel");

        $response->assertOk()
            ->assertJsonPath('data.status', Order::STATUS_CANCELLED);

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => Order::STATUS_CANCELLED,
        ]);
    }

    public function test_can_cancel_processing_order(): void
    {
        $user = User::factory()->create();
        $order = Order::factory()->create([
            'user_id' => $user->id,
            'status' => Order::STATUS_PROCESSING,
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson("/api/orders/{$order->id}/cancel");

        $response->assertOk()
            ->assertJsonPath('data.status', Order::STATUS_CANCELLED);
    }

    public function test_cannot_cancel_shipped_order(): void
    {
        $user = User::factory()->create();
        $order = Order::factory()->create([
            'user_id' => $user->id,
            'status' => Order::STATUS_SHIPPED,
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson("/api/orders/{$order->id}/cancel");

        $response->assertStatus(422)
            ->assertJsonPath('message', 'Pesanan tidak dapat dibatalkan');
    }

    public function test_cannot_cancel_delivered_order(): void
    {
        $user = User::factory()->create();
        $order = Order::factory()->create([
            'user_id' => $user->id,
            'status' => Order::STATUS_DELIVERED,
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson("/api/orders/{$order->id}/cancel");

        $response->assertStatus(422);
    }

    public function test_stock_restored_after_cancel(): void
    {
        $user = User::factory()->create();
        $category = Category::create(['name' => 'Test Category', 'slug' => 'test-category']);
        $brand = Brand::create(['name' => 'Test Brand', 'slug' => 'test-brand']);
        $product = Product::create([
            'brand_id' => $brand->id,
            'category_id' => $category->id,
            'name' => 'Test Product',
            'slug' => 'test-product',
            'is_active' => true,
        ]);
        $variant = ProductVariant::create([
            'product_id' => $product->id,
            'label' => 'Size M',
            'price' => 50000,
            'sku' => 'TST-001',
            'is_active' => true,
        ]);
        $warehouse = Warehouse::create([
            'name' => 'Main Warehouse',
            'is_active' => true,
        ]);

        WarehouseStock::create([
            'warehouse_id' => $warehouse->id,
            'product_variant_id' => $variant->id,
            'quantity' => 10,
        ]);

        $order = Order::factory()->create([
            'user_id' => $user->id,
            'status' => Order::STATUS_PENDING,
        ]);

        OrderItem::factory()->create([
            'order_id' => $order->id,
            'product_variant_id' => $variant->id,
            'quantity' => 3,
            'subtotal' => 150000,
        ]);

        $this->actingAs($user, 'sanctum')
            ->postJson("/api/orders/{$order->id}/cancel");

        $this->assertDatabaseHas('warehouse_stocks', [
            'product_variant_id' => $variant->id,
            'warehouse_id' => $warehouse->id,
            'quantity' => 13,
        ]);
    }

    public function test_points_restored_after_cancel(): void
    {
        $user = User::factory()->create();
        UserPoint::create([
            'user_id' => $user->id,
            'balance' => 500,
        ]);

        $order = Order::factory()->create([
            'user_id' => $user->id,
            'status' => Order::STATUS_PROCESSING,
            'point_redeemed' => 200,
        ]);

        $this->actingAs($user, 'sanctum')
            ->postJson("/api/orders/{$order->id}/cancel");

        $this->assertDatabaseHas('user_points', [
            'user_id' => $user->id,
            'balance' => 700,
        ]);

        $this->assertDatabaseHas('point_transactions', [
            'user_id' => $user->id,
            'order_id' => $order->id,
            'type' => 1,
            'amount' => 200,
        ]);
    }

    public function test_cannot_cancel_other_users_order(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();
        $order = Order::factory()->create([
            'user_id' => $userA->id,
            'status' => Order::STATUS_PENDING,
        ]);

        $response = $this->actingAs($userB, 'sanctum')
            ->postJson("/api/orders/{$order->id}/cancel");

        $response->assertNotFound();
    }
}
