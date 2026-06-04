<?php

namespace Tests\Feature\Api\Checkout;

use App\Models\Cart;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\WarehouseStock;
use App\Testing\Traits\CreatesBusinessSchema;
use Tests\TestCase;

class ConcurrentCheckoutTest extends TestCase
{
    use CreatesBusinessSchema;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpBusinessSchema();
    }

    public function test_concurrent_checkout_prevents_double_deduction(): void
    {
        $product = Product::create(['name' => 'Test', 'slug' => 'test-' . uniqid(), 'is_active' => 1]);

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
            'quantity' => 5,
        ]);

        $userA = User::factory()->create();
        Cart::create(['user_id' => $userA->id, 'product_variant_id' => $variant->id, 'quantity' => 3]);
        $tokenA = $userA->createToken('auth-token')->plainTextToken;

        $userB = User::factory()->create();
        Cart::create(['user_id' => $userB->id, 'product_variant_id' => $variant->id, 'quantity' => 3]);
        $tokenB = $userB->createToken('auth-token')->plainTextToken;

        $responseA = $this->withToken($tokenA)->postJson('/api/checkout', [
            'shipping_address' => 'Alamat A',
        ]);

        $responseB = $this->withToken($tokenB)->postJson('/api/checkout', [
            'shipping_address' => 'Alamat B',
        ]);

        $successCount = 0;
        $failCount = 0;

        if ($responseA->status() === 201) {
            $successCount++;
        } else {
            $failCount++;
        }

        if ($responseB->status() === 201) {
            $successCount++;
        } else {
            $failCount++;
        }

        $this->assertEquals(1, $successCount, 'Hanya 1 checkout yang harus sukses');
        $this->assertEquals(1, $failCount, '1 checkout harus gagal karena stock tidak cukup');

        $this->assertDatabaseHas('warehouse_stocks', [
            'product_variant_id' => $variant->id,
            'quantity' => 2,
        ], 'Expected final stock to be 2 (5 - 3 = 2)');
    }
}
