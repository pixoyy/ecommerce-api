<?php

namespace Tests\Feature\Api\Integration;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Order;
use App\Models\PaymentAccount;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\WarehouseStock;
use App\Testing\Traits\CreatesBusinessSchema;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class FullFlowTest extends TestCase
{
    use CreatesBusinessSchema;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpBusinessSchema();
    }

    public function test_full_customer_journey(): void
    {
        $category = Category::create(['name' => 'Elektronik', 'slug' => 'elektronik-' . uniqid(), 'is_active' => 1]);
        $brand = Brand::create(['name' => 'Samsung', 'slug' => 'samsung-' . uniqid()]);

        $product = Product::create([
            'category_id' => $category->id,
            'brand_id' => $brand->id,
            'name' => 'Smartphone Galaxy',
            'slug' => 'smartphone-' . uniqid(),
            'is_active' => 1,
        ]);

        $variant = ProductVariant::create([
            'product_id' => $product->id,
            'label' => '128GB',
            'price' => 5000000,
            'is_active' => 1,
        ]);

        $warehouse = Warehouse::create(['name' => 'Gudang Utama']);
        WarehouseStock::create([
            'warehouse_id' => $warehouse->id,
            'product_variant_id' => $variant->id,
            'quantity' => 10,
        ]);

        PaymentAccount::create([
            'bank_name' => 'BCA',
            'account_number' => '1234567890',
            'account_name' => 'PT Ecommerce',
            'is_active' => 1,
        ]);

        $registerResponse = $this->postJson('/api/register', [
            'name' => 'Budi Santoso',
            'email' => 'budi@example.com',
            'phone' => '08123456789',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);
        $registerResponse->assertStatus(201);
        $token = $registerResponse->json('data.token');

        $this->getJson('/api/categories')->assertStatus(200);
        $this->getJson('/api/brands')->assertStatus(200);
        $this->getJson('/api/products')->assertStatus(200);

        $this->getJson("/api/products/{$product->slug}")->assertStatus(200);

        $cartResponse = $this->withToken($token)->postJson('/api/cart/items', [
            'product_variant_id' => $variant->id,
            'quantity' => 2,
        ]);
        $cartResponse->assertStatus(201);

        $checkoutResponse = $this->withToken($token)->postJson('/api/checkout', [
            'shipping_address' => 'Jl. Merdeka No. 1, Jakarta',
            'shipping_note' => 'Pagi hari',
        ]);
        $checkoutResponse->assertStatus(201);
        $orderNumber = $checkoutResponse->json('data.order_number');

        $this->withToken($token)->getJson('/api/cart')
            ->assertJson(['data' => []]);

        $order = Order::where('order_number', $orderNumber)->first();
        $file = UploadedFile::fake()->image('payment.jpg', 500, 500);
        $paymentResponse = $this->withToken($token)->postJson("/api/orders/{$order->id}/payment", [
            'proof' => $file,
            'amount' => $order->total,
        ]);
        $paymentResponse->assertStatus(201);

        $this->withToken($token)->getJson("/api/orders/{$orderNumber}")
            ->assertStatus(200);

        $this->withToken($token)->getJson('/api/rewards/balance')
            ->assertStatus(200);
        $this->withToken($token)->getJson('/api/rewards/transactions')
            ->assertStatus(200);

        $this->withToken($token)->putJson('/api/profile', [
            'name' => 'Budi Santoso Update',
            'email' => 'budi@example.com',
            'phone' => '08123456788',
        ])->assertStatus(200);
    }
}
