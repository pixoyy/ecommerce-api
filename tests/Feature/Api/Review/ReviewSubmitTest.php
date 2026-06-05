<?php

namespace Tests\Feature\Api\Review;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReviewSubmitTest extends TestCase
{
    use RefreshDatabase;

    private function createDeliveredOrder(User $user): array
    {
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

        $order = Order::factory()->create([
            'user_id' => $user->id,
            'status' => Order::STATUS_DELIVERED,
        ]);

        OrderItem::factory()->create([
            'order_id' => $order->id,
            'product_variant_id' => $variant->id,
            'product_name' => $product->name,
            'variant_label' => $variant->label,
        ]);

        return [$order, $variant];
    }

    public function test_can_submit_review_for_delivered_order(): void
    {
        $user = User::factory()->create();
        [$order, $variant] = $this->createDeliveredOrder($user);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson("/api/orders/{$order->id}/reviews", [
                'product_variant_id' => $variant->id,
                'rating' => 5,
                'review' => 'Produk bagus sekali!',
            ]);

        $response->assertCreated()
            ->assertJsonPath('data.rating', 5)
            ->assertJsonPath('data.review', 'Produk bagus sekali!');

        $this->assertDatabaseHas('reviews', [
            'user_id' => $user->id,
            'rating' => 5,
            'reason' => 'Produk bagus sekali!',
            'is_visible' => true,
        ]);
    }

    public function test_cannot_submit_review_for_non_delivered_order(): void
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

        foreach ([Order::STATUS_PENDING, Order::STATUS_PROCESSING, Order::STATUS_SHIPPED] as $status) {
            $order = Order::factory()->create([
                'user_id' => $user->id,
                'status' => $status,
            ]);

            $response = $this->actingAs($user, 'sanctum')
                ->postJson("/api/orders/{$order->id}/reviews", [
                    'product_variant_id' => $variant->id,
                    'rating' => 4,
                ]);

            $response->assertStatus(422)
                ->assertJsonPath('message', 'Ulasan hanya dapat diberikan untuk pesanan yang sudah selesai');
        }
    }

    public function test_cannot_submit_duplicate_review(): void
    {
        $user = User::factory()->create();
        [$order, $variant] = $this->createDeliveredOrder($user);

        $this->actingAs($user, 'sanctum')
            ->postJson("/api/orders/{$order->id}/reviews", [
                'product_variant_id' => $variant->id,
                'rating' => 5,
                'review' => 'Bagus!',
            ]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson("/api/orders/{$order->id}/reviews", [
                'product_variant_id' => $variant->id,
                'rating' => 4,
                'review' => 'Coba lagi',
            ]);

        $response->assertStatus(422)
            ->assertJsonPath('message', 'Anda sudah memberikan ulasan untuk produk ini');
    }

    public function test_cannot_review_product_not_in_order(): void
    {
        $user = User::factory()->create();
        [$order] = $this->createDeliveredOrder($user);

        $category = Category::create(['name' => 'Other', 'slug' => 'other']);
        $brand = Brand::create(['name' => 'Other', 'slug' => 'other']);
        $otherProduct = Product::create([
            'brand_id' => $brand->id,
            'category_id' => $category->id,
            'name' => 'Other Product',
            'slug' => 'other-product',
            'is_active' => true,
        ]);
        $otherVariant = ProductVariant::create([
            'product_id' => $otherProduct->id,
            'label' => 'Size L',
            'price' => 75000,
            'sku' => 'OTH-001',
            'is_active' => true,
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson("/api/orders/{$order->id}/reviews", [
                'product_variant_id' => $otherVariant->id,
                'rating' => 3,
            ]);

        $response->assertStatus(422)
            ->assertJsonPath('message', 'Varian produk tidak ditemukan di pesanan ini');
    }

    public function test_rating_validation_min_1_max_5(): void
    {
        $user = User::factory()->create();
        [$order, $variant] = $this->createDeliveredOrder($user);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson("/api/orders/{$order->id}/reviews", [
                'product_variant_id' => $variant->id,
                'rating' => 0,
            ]);

        $response->assertStatus(422);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson("/api/orders/{$order->id}/reviews", [
                'product_variant_id' => $variant->id,
                'rating' => 6,
            ]);

        $response->assertStatus(422);
    }

    public function test_cannot_review_other_users_order(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();
        [$order, $variant] = $this->createDeliveredOrder($userA);

        $response = $this->actingAs($userB, 'sanctum')
            ->postJson("/api/orders/{$order->id}/reviews", [
                'product_variant_id' => $variant->id,
                'rating' => 4,
            ]);

        $response->assertNotFound();
    }

    public function test_review_optional_fields(): void
    {
        $user = User::factory()->create();
        [$order, $variant] = $this->createDeliveredOrder($user);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson("/api/orders/{$order->id}/reviews", [
                'product_variant_id' => $variant->id,
                'rating' => 3,
            ]);

        $response->assertCreated()
            ->assertJsonPath('data.rating', 3)
            ->assertJsonMissingPath('data.review');
    }
}
