<?php

namespace Tests\Feature\Api\Payment;

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

class PaymentTest extends TestCase
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

    private function createPendingOrder(): Order
    {
        $product = Product::create(['name' => 'Test', 'slug' => 'test-' . uniqid(), 'is_active' => 1]);
        $variant = ProductVariant::create(['product_id' => $product->id, 'label' => 'V1', 'price' => 50000, 'is_active' => 1]);
        $warehouse = Warehouse::create(['name' => 'Gudang']);
        WarehouseStock::create(['warehouse_id' => $warehouse->id, 'product_variant_id' => $variant->id, 'quantity' => 10]);

        return Order::create([
            'user_id' => $this->user->id,
            'order_number' => 'INV/' . now()->format('Ymd') . '/00001',
            'status' => Order::STATUS_PENDING,
            'subtotal' => 50000,
            'shipping_cost' => 15000,
            'total' => 65000,
            'shipping_address' => 'Jl. Merdeka No. 1',
        ]);
    }

    public function test_can_list_active_payment_accounts(): void
    {
        PaymentAccount::create(['bank_name' => 'BCA', 'account_number' => '123', 'account_name' => 'PT ABC', 'is_active' => 1]);
        PaymentAccount::create(['bank_name' => 'Mandiri', 'account_number' => '456', 'account_name' => 'PT ABC', 'is_active' => 0]);

        $response = $this->getJson('/api/payment-accounts');

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data'));
        $this->assertEquals('BCA', $response->json('data.0.bank_name'));
    }

    public function test_can_upload_payment_proof(): void
    {
        $order = $this->createPendingOrder();

        $response = $this->postJson("/api/orders/{$order->id}/payment", [
            'proof' => UploadedFile::fake()->image('proof.jpg'),
            'amount' => 65000,
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'data' => ['id', 'amount', 'status', 'status_label', 'proof_url', 'created_at'],
            ]);

        $this->assertEquals(1, $response->json('data.status'));
        $this->assertEquals('Menunggu Konfirmasi', $response->json('data.status_label'));
    }

    public function test_cannot_upload_payment_for_wrong_order(): void
    {
        $order = $this->createPendingOrder();

        $otherUser = User::factory()->create();
        $otherToken = $otherUser->createToken('auth-token')->plainTextToken;

        $response = $this->withToken($otherToken)->postJson("/api/orders/{$order->id}/payment", [
            'proof' => UploadedFile::fake()->image('proof.jpg'),
            'amount' => 65000,
        ]);

        $response->assertStatus(404);
    }

    public function test_cannot_upload_payment_for_non_pending_order(): void
    {
        $order = $this->createPendingOrder();
        $order->update(['status' => Order::STATUS_PROCESSING]);

        $response = $this->postJson("/api/orders/{$order->id}/payment", [
            'proof' => UploadedFile::fake()->image('proof.jpg'),
            'amount' => 65000,
        ]);

        $response->assertStatus(422);
    }

    public function test_cannot_upload_duplicate_payment(): void
    {
        $order = $this->createPendingOrder();

        $this->postJson("/api/orders/{$order->id}/payment", [
            'proof' => UploadedFile::fake()->image('proof.jpg'),
            'amount' => 65000,
        ]);

        $response = $this->postJson("/api/orders/{$order->id}/payment", [
            'proof' => UploadedFile::fake()->image('proof2.jpg'),
            'amount' => 65000,
        ]);

        $response->assertStatus(422);
    }

    public function test_payment_amount_must_match_order_total(): void
    {
        $order = $this->createPendingOrder();

        $response = $this->postJson("/api/orders/{$order->id}/payment", [
            'proof' => UploadedFile::fake()->image('proof.jpg'),
            'amount' => 10000,
        ]);

        $response->assertStatus(422);
    }

    public function test_payment_requires_image_file(): void
    {
        $order = $this->createPendingOrder();

        $response = $this->postJson("/api/orders/{$order->id}/payment", [
            'proof' => UploadedFile::fake()->create('document.pdf', 100),
            'amount' => 65000,
        ]);

        $response->assertStatus(422);
    }

    public function test_uploaded_file_stored_in_storage(): void
    {
        $order = $this->createPendingOrder();

        $response = $this->postJson("/api/orders/{$order->id}/payment", [
            'proof' => UploadedFile::fake()->image('proof.jpg'),
            'amount' => 65000,
        ]);

        $response->assertStatus(201);

        $this->assertDatabaseHas('file_storages', [
            'link' => $response->json('data.proof_url'),
        ]);
    }
}
