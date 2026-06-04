<?php

namespace Tests\Feature\Api\Cart;

use App\Models\Cart;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\WarehouseStock;
use App\Testing\Traits\CreatesBusinessSchema;
use Tests\TestCase;

class CartTest extends TestCase
{
    use CreatesBusinessSchema;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpBusinessSchema();
    }

    private function createAuthenticatedUser(): User
    {
        $user = User::factory()->create();
        $token = $user->createToken('auth-token')->plainTextToken;
        $this->withToken($token);

        return $user;
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

    public function test_can_view_empty_cart(): void
    {
        $this->createAuthenticatedUser();

        $response = $this->getJson('/api/cart');

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->assertEmpty($response->json('data'));
    }

    public function test_can_add_item_to_cart(): void
    {
        $this->createAuthenticatedUser();
        $variant = $this->createVariantWithStock(10);

        $response = $this->postJson('/api/cart/items', [
            'product_variant_id' => $variant->id,
            'quantity' => 2,
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'id', 'product_variant_id', 'product_name', 'variant_label',
                    'product_slug', 'product_thumbnail', 'unit_price',
                    'promo_price', 'current_price', 'quantity', 'subtotal',
                    'stock_available', 'is_stock_sufficient',
                ],
            ]);

        $this->assertEquals(2, $response->json('data.quantity'));
    }

    public function test_add_existing_item_increments_quantity(): void
    {
        $this->createAuthenticatedUser();
        $variant = $this->createVariantWithStock(100);

        $this->postJson('/api/cart/items', [
            'product_variant_id' => $variant->id,
            'quantity' => 1,
        ]);

        $response = $this->postJson('/api/cart/items', [
            'product_variant_id' => $variant->id,
            'quantity' => 1,
        ]);

        $response->assertStatus(201);
        $this->assertEquals(2, $response->json('data.quantity'));
    }

    public function test_cannot_add_inactive_variant(): void
    {
        $this->createAuthenticatedUser();
        $product = Product::create(['name' => 'Test', 'slug' => 'test-' . uniqid(), 'is_active' => 1]);
        $variant = ProductVariant::create([
            'product_id' => $product->id,
            'label' => 'Inactive',
            'price' => 100,
            'is_active' => 0,
        ]);

        $response = $this->postJson('/api/cart/items', [
            'product_variant_id' => $variant->id,
            'quantity' => 1,
        ]);

        $response->assertStatus(422);
    }

    public function test_cannot_add_variant_without_stock(): void
    {
        $this->createAuthenticatedUser();
        $variant = $this->createVariantWithStock(0);

        $response = $this->postJson('/api/cart/items', [
            'product_variant_id' => $variant->id,
            'quantity' => 1,
        ]);

        $response->assertStatus(422);
    }

    public function test_can_update_item_quantity(): void
    {
        $this->createAuthenticatedUser();
        $variant = $this->createVariantWithStock(50);

        $addResponse = $this->postJson('/api/cart/items', [
            'product_variant_id' => $variant->id,
            'quantity' => 1,
        ]);

        $cartId = $addResponse->json('data.id');

        $response = $this->putJson("/api/cart/items/{$cartId}", [
            'quantity' => 5,
        ]);

        $response->assertStatus(200);
        $this->assertEquals(5, $response->json('data.quantity'));
    }

    public function test_cannot_update_to_quantity_zero(): void
    {
        $this->createAuthenticatedUser();
        $variant = $this->createVariantWithStock(10);

        $addResponse = $this->postJson('/api/cart/items', [
            'product_variant_id' => $variant->id,
            'quantity' => 1,
        ]);

        $cartId = $addResponse->json('data.id');

        $response = $this->putJson("/api/cart/items/{$cartId}", [
            'quantity' => 0,
        ]);

        $response->assertStatus(422);
    }

    public function test_can_remove_item_from_cart(): void
    {
        $this->createAuthenticatedUser();
        $variant = $this->createVariantWithStock(10);

        $addResponse = $this->postJson('/api/cart/items', [
            'product_variant_id' => $variant->id,
            'quantity' => 1,
        ]);

        $cartId = $addResponse->json('data.id');

        $deleteResponse = $this->deleteJson("/api/cart/items/{$cartId}");
        $deleteResponse->assertStatus(200);

        $getResponse = $this->getJson('/api/cart');
        $this->assertEmpty($getResponse->json('data'));
    }

    public function test_cart_requires_authentication(): void
    {
        $responses = [
            $this->getJson('/api/cart'),
            $this->postJson('/api/cart/items', ['product_variant_id' => 1, 'quantity' => 1]),
            $this->putJson('/api/cart/items/1', ['quantity' => 1]),
            $this->deleteJson('/api/cart/items/1'),
        ];

        foreach ($responses as $response) {
            $response->assertStatus(401);
        }
    }

    public function test_cannot_access_other_users_cart_item(): void
    {
        $userA = User::factory()->create();
        $tokenA = $userA->createToken('auth-token')->plainTextToken;

        $variant = $this->createVariantWithStock(10);
        $cart = Cart::create([
            'user_id' => $userA->id,
            'product_variant_id' => $variant->id,
            'quantity' => 1,
        ]);

        $userB = User::factory()->create();
        $tokenB = $userB->createToken('auth-token')->plainTextToken;

        $this->withToken($tokenB);

        $updateResponse = $this->putJson("/api/cart/items/{$cart->id}", ['quantity' => 2]);
        $updateResponse->assertStatus(404);

        $deleteResponse = $this->deleteJson("/api/cart/items/{$cart->id}");
        $deleteResponse->assertStatus(404);
    }
}
