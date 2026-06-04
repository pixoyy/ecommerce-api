<?php

namespace Tests\Feature\Api\Checkout;

use App\Models\Cart;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Promotion;
use App\Models\PromotionItem;
use App\Models\User;
use App\Models\UserPoint;
use App\Models\Warehouse;
use App\Models\WarehouseStock;
use App\Testing\Traits\CreatesBusinessSchema;
use Tests\TestCase;

class CheckoutTest extends TestCase
{
    use CreatesBusinessSchema;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpBusinessSchema();

        $this->user = User::factory()->create();
        $token = $this->user->createToken('auth-token')->plainTextToken;
        $this->withToken($token);
    }

    private function createVariantWithStock(int $stockQty = 10): ProductVariant
    {
        $product = Product::create([
            'name' => 'Test Product',
            'slug' => 'test-product-' . uniqid(),
            'is_active' => 1,
        ]);

        $variant = ProductVariant::create([
            'product_id' => $product->id,
            'label' => 'V1',
            'sku' => 'SKU-001',
            'price' => 50000,
            'is_active' => 1,
        ]);

        $warehouse = Warehouse::create(['name' => 'Gudang']);
        WarehouseStock::create([
            'warehouse_id' => $warehouse->id,
            'product_variant_id' => $variant->id,
            'quantity' => $stockQty,
        ]);

        return $variant;
    }

    private function addToCart(int $variantId, int $quantity = 1): void
    {
        Cart::create([
            'user_id' => $this->user->id,
            'product_variant_id' => $variantId,
            'quantity' => $quantity,
        ]);
    }

    public function test_can_checkout_successfully(): void
    {
        $variant = $this->createVariantWithStock(10);
        $this->addToCart($variant->id, 2);

        $response = $this->postJson('/api/checkout', [
            'shipping_address' => 'Jl. Merdeka No. 1, Jakarta',
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'id', 'order_number', 'status', 'status_label',
                    'subtotal', 'shipping_cost', 'point_redeemed', 'point_earned',
                    'total', 'items_count', 'created_at',
                ],
            ]);

        $this->assertEquals(100000, (int) $response->json('data.subtotal'));
        $this->assertEquals(15000, (int) $response->json('data.shipping_cost'));
        $this->assertEquals(115000, (int) $response->json('data.total'));
        $this->assertEquals(1, $response->json('data.status'));
        $this->assertEquals('Menunggu Pembayaran', $response->json('data.status_label'));
    }

    public function test_checkout_with_empty_cart_returns_error(): void
    {
        $response = $this->postJson('/api/checkout', [
            'shipping_address' => 'Jl. Merdeka No. 1',
        ]);

        $response->assertStatus(422)
            ->assertJson(['message' => 'Keranjang belanja kosong']);
    }

    public function test_checkout_with_insufficient_stock_returns_error(): void
    {
        $variant = $this->createVariantWithStock(1);
        $this->addToCart($variant->id, 5);

        $response = $this->postJson('/api/checkout', [
            'shipping_address' => 'Jl. Merdeka No. 1',
        ]);

        $response->assertStatus(422);
    }

    public function test_checkout_with_promotion_applied(): void
    {
        $variant = $this->createVariantWithStock(10);

        $promotion = Promotion::create([
            'name' => 'Diskon 10%',
            'is_active' => 1,
            'start_at' => now()->subDay(),
            'end_at' => now()->addDay(),
        ]);

        PromotionItem::create([
            'promotion_id' => $promotion->id,
            'product_variant_id' => $variant->id,
            'override_price' => 40000,
        ]);

        $this->addToCart($variant->id, 2);

        $response = $this->postJson('/api/checkout', [
            'shipping_address' => 'Jl. Merdeka No. 1',
        ]);

        $response->assertStatus(201);
        $this->assertEquals(80000, (int) $response->json('data.subtotal'));
        $this->assertEquals(95000, (int) $response->json('data.total'));
    }

    public function test_checkout_with_point_redeemed(): void
    {
        UserPoint::create(['user_id' => $this->user->id, 'balance' => 50000]);

        $variant = $this->createVariantWithStock(10);
        $this->addToCart($variant->id, 1);

        $response = $this->postJson('/api/checkout', [
            'shipping_address' => 'Jl. Merdeka No. 1',
            'redeem_points' => 10000,
        ]);

        $response->assertStatus(201);
        $this->assertEquals(50000, (int) $response->json('data.subtotal'));
        $this->assertEquals(10000, (int) $response->json('data.point_redeemed'));
        $this->assertEquals(55000, (int) $response->json('data.total'));

        $this->assertDatabaseHas('user_points', [
            'user_id' => $this->user->id,
            'balance' => 40000,
        ]);

        $this->assertDatabaseHas('point_transactions', [
            'user_id' => $this->user->id,
            'type' => 2,
            'amount' => 10000,
        ]);
    }

    public function test_checkout_deducts_stock_correctly(): void
    {
        $variant = $this->createVariantWithStock(10);
        $this->addToCart($variant->id, 3);

        $this->postJson('/api/checkout', [
            'shipping_address' => 'Jl. Merdeka No. 1',
        ]);

        $this->assertDatabaseHas('warehouse_stocks', [
            'product_variant_id' => $variant->id,
            'quantity' => 7,
        ]);
    }

    public function test_checkout_clears_cart(): void
    {
        $variant = $this->createVariantWithStock(10);
        $this->addToCart($variant->id, 1);

        $this->postJson('/api/checkout', [
            'shipping_address' => 'Jl. Merdeka No. 1',
        ]);

        $this->assertDatabaseMissing('carts', [
            'user_id' => $this->user->id,
        ]);
    }

    public function test_order_number_format(): void
    {
        $variant = $this->createVariantWithStock(10);
        $this->addToCart($variant->id, 1);

        $response = $this->postJson('/api/checkout', [
            'shipping_address' => 'Jl. Merdeka No. 1',
        ]);

        $response->assertStatus(201);

        $orderNumber = $response->json('data.order_number');
        $this->assertMatchesRegularExpression('/^INV\/\d{8}\/\d{5}$/', $orderNumber);
    }
}
