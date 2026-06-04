<?php

namespace Tests\Feature\Api\Catalog;

use App\Models\Product;
use App\Models\Review;
use App\Models\User;
use App\Testing\Traits\CreatesBusinessSchema;
use Tests\TestCase;

class ProductReviewTest extends TestCase
{
    use CreatesBusinessSchema;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpBusinessSchema();
    }

    public function test_can_get_product_reviews(): void
    {
        $user = User::factory()->create();
        $product = Product::create(['name' => 'Test', 'slug' => 'test', 'is_active' => 1]);

        Review::create([
            'user_id' => $user->id,
            'product_id' => $product->id,
            'rating' => 5,
            'reason' => 'Bagus sekali',
            'is_visible' => 1,
        ]);

        $response = $this->getJson("/api/products/{$product->id}/reviews");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => ['id', 'user_name', 'rating', 'review', 'created_at'],
                ],
            ]);

        $this->assertCount(1, $response->json('data'));
        $this->assertEquals('Bagus sekali', $response->json('data.0.review'));
    }

    public function test_only_visible_reviews_shown(): void
    {
        $user = User::factory()->create();
        $product = Product::create(['name' => 'Test', 'slug' => 'test', 'is_active' => 1]);

        Review::create([
            'user_id' => $user->id,
            'product_id' => $product->id,
            'rating' => 5,
            'reason' => 'Visible',
            'is_visible' => 1,
        ]);

        Review::create([
            'user_id' => $user->id,
            'product_id' => $product->id,
            'rating' => 1,
            'reason' => 'Hidden',
            'is_visible' => 0,
        ]);

        $response = $this->getJson("/api/products/{$product->id}/reviews");

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data'));
        $this->assertEquals('Visible', $response->json('data.0.review'));
    }

    public function test_inactive_product_reviews_returns_404(): void
    {
        $product = Product::create(['name' => 'Inactive', 'slug' => 'inactive', 'is_active' => 0]);

        $response = $this->getJson("/api/products/{$product->id}/reviews");

        $response->assertStatus(404);
    }
}
