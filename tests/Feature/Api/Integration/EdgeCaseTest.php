<?php

namespace Tests\Feature\Api\Integration;

use App\Models\Brand;
use App\Models\Cart;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\WarehouseStock;
use App\Testing\Traits\CreatesBusinessSchema;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class EdgeCaseTest extends TestCase
{
    use CreatesBusinessSchema;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpBusinessSchema();
    }

    public function test_concurrent_checkout_race_condition(): void
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

        $users = [];
        $tokens = [];
        for ($i = 0; $i < 3; $i++) {
            $user = User::factory()->create();
            Cart::create(['user_id' => $user->id, 'product_variant_id' => $variant->id, 'quantity' => 3]);
            $token = $user->createToken('auth-token')->plainTextToken;
            $users[] = $user;
            $tokens[] = $token;
        }

        $responses = [];
        foreach ($tokens as $token) {
            $responses[] = $this->withToken($token)->postJson('/api/checkout', [
                'shipping_address' => 'Alamat',
            ]);
        }

        $successCount = 0;
        $failCount = 0;

        foreach ($responses as $response) {
            if ($response->status() === 201) {
                $successCount++;
            } else {
                $failCount++;
            }
        }

        $this->assertEquals(1, $successCount, 'Hanya 1 checkout yang harus sukses dari 3 percobaan');
        $this->assertEquals(2, $failCount, '2 checkout harus gagal karena stock tidak cukup');

        $this->assertDatabaseHas('warehouse_stocks', [
            'product_variant_id' => $variant->id,
            'quantity' => 2,
        ]);
    }

    public function test_cancel_after_payment_upload(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('auth-token')->plainTextToken;

        $product = Product::create(['name' => 'Test', 'slug' => 'test-' . uniqid(), 'is_active' => 1]);
        $variant = ProductVariant::create(['product_id' => $product->id, 'label' => 'V1', 'price' => 50000, 'is_active' => 1]);
        $warehouse = Warehouse::create(['name' => 'Gudang']);
        WarehouseStock::create(['warehouse_id' => $warehouse->id, 'product_variant_id' => $variant->id, 'quantity' => 10]);

        Cart::create(['user_id' => $user->id, 'product_variant_id' => $variant->id, 'quantity' => 1]);

        $checkoutResponse = $this->withToken($token)->postJson('/api/checkout', [
            'shipping_address' => 'Jl. Merdeka',
        ]);
        $checkoutResponse->assertStatus(201);

        $order = Order::where('user_id', $user->id)->first();

        $file = UploadedFile::fake()->image('proof.jpg');
        $paymentResponse = $this->withToken($token)->postJson("/api/orders/{$order->id}/payment", [
            'proof' => $file,
            'amount' => $order->total,
        ]);
        $paymentResponse->assertStatus(201);

        $cancelResponse = $this->withToken($token)->postJson("/api/orders/{$order->id}/cancel");
        $cancelResponse->assertOk()
            ->assertJsonPath('data.status', Order::STATUS_CANCELLED);
    }

    public function test_duplicate_payment_upload(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('auth-token')->plainTextToken;

        $product = Product::create(['name' => 'Test', 'slug' => 'test-' . uniqid(), 'is_active' => 1]);
        $variant = ProductVariant::create(['product_id' => $product->id, 'label' => 'V1', 'price' => 50000, 'is_active' => 1]);
        $warehouse = Warehouse::create(['name' => 'Gudang']);
        WarehouseStock::create(['warehouse_id' => $warehouse->id, 'product_variant_id' => $variant->id, 'quantity' => 10]);

        Cart::create(['user_id' => $user->id, 'product_variant_id' => $variant->id, 'quantity' => 1]);

        $this->withToken($token)->postJson('/api/checkout', ['shipping_address' => 'Jl. Merdeka'])->assertStatus(201);

        $order = Order::where('user_id', $user->id)->first();

        $this->withToken($token)->postJson("/api/orders/{$order->id}/payment", [
            'proof' => UploadedFile::fake()->image('proof1.jpg'),
            'amount' => $order->total,
        ])->assertStatus(201);

        $response = $this->withToken($token)->postJson("/api/orders/{$order->id}/payment", [
            'proof' => UploadedFile::fake()->image('proof2.jpg'),
            'amount' => $order->total,
        ]);
        $response->assertStatus(422);
    }

    public function test_review_after_cancel(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('auth-token')->plainTextToken;

        $category = Category::create(['name' => 'Test', 'slug' => 'test-cat-' . uniqid()]);
        $brand = Brand::create(['name' => 'Test', 'slug' => 'test-brand-' . uniqid()]);
        $product = Product::create([
            'category_id' => $category->id,
            'brand_id' => $brand->id,
            'name' => 'Test Product',
            'slug' => 'test-product-' . uniqid(),
            'is_active' => 1,
        ]);
        $variant = ProductVariant::create([
            'product_id' => $product->id,
            'label' => 'Size M',
            'price' => 50000,
            'is_active' => 1,
        ]);

        $warehouse = Warehouse::create(['name' => 'Gudang']);
        WarehouseStock::create(['warehouse_id' => $warehouse->id, 'product_variant_id' => $variant->id, 'quantity' => 10]);

        Cart::create(['user_id' => $user->id, 'product_variant_id' => $variant->id, 'quantity' => 1]);

        $checkoutResponse = $this->withToken($token)->postJson('/api/checkout', [
            'shipping_address' => 'Alamat',
        ]);
        $checkoutResponse->assertStatus(201);

        $order = Order::where('user_id', $user->id)->first();

        $this->withToken($token)->postJson("/api/orders/{$order->id}/cancel")->assertOk();

        $response = $this->withToken($token)->postJson("/api/orders/{$order->id}/reviews", [
            'product_variant_id' => $variant->id,
            'rating' => 5,
        ]);
        $response->assertStatus(422);
    }

    public function test_access_expired_token(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('auth-token')->plainTextToken;

        $user->tokens()->delete();

        $response = $this->withToken($token)->getJson('/api/me');

        $response->assertStatus(401);
    }

    public function test_soft_deleted_user_login(): void
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password123'),
        ]);
        $user->delete();

        $response = $this->postJson('/api/login', [
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }
}
