<?php

namespace Tests\Feature\Api\Catalog;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use App\Models\Promotion;
use App\Models\PromotionItem;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\WarehouseStock;
use App\Testing\Traits\CreatesBusinessSchema;
use Tests\TestCase;

class ProductDetailTest extends TestCase
{
    use CreatesBusinessSchema;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpBusinessSchema();
    }

    public function test_can_get_product_detail_by_slug(): void
    {
        $category = Category::create(['name' => 'Elektronik', 'slug' => 'elektronik', 'is_active' => 1]);
        $brand = Brand::create(['name' => 'Sony', 'slug' => 'sony']);

        $product = Product::create([
            'name' => 'Smartphone',
            'slug' => 'smartphone',
            'description' => 'Desc',
            'features' => 'Fitur A, Fitur B',
            'category_id' => $category->id,
            'brand_id' => $brand->id,
            'gender' => 0,
            'is_active' => 1,
        ]);

        $variant = ProductVariant::create([
            'product_id' => $product->id,
            'label' => '64GB',
            'price' => 5000000,
            'is_active' => 1,
        ]);

        $response = $this->getJson("/api/products/{$product->slug}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'id', 'name', 'slug', 'description', 'features', 'thumbnail',
                    'category' => ['id', 'name', 'slug', 'image'],
                    'brand' => ['id', 'name', 'slug'],
                    'gender', 'gender_label',
                    'images',
                    'variants' => [
                        '*' => ['id', 'label', 'sku', 'price', 'promo_price', 'stock', 'is_active'],
                    ],
                    'average_rating', 'total_reviews', 'is_active',
                ],
            ]);

        $response->assertJsonPath('data.name', 'Smartphone');
        $response->assertJsonPath('data.variants.0.label', '64GB');
    }

    public function test_product_detail_includes_active_variants_only(): void
    {
        $product = Product::create(['name' => 'Test', 'slug' => 'test', 'is_active' => 1]);

        ProductVariant::create(['product_id' => $product->id, 'label' => 'Active', 'price' => 100, 'is_active' => 1]);
        ProductVariant::create(['product_id' => $product->id, 'label' => 'Inactive', 'price' => 200, 'is_active' => 0]);

        $response = $this->getJson("/api/products/{$product->slug}");

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data.variants'));
        $this->assertEquals('Active', $response->json('data.variants.0.label'));
    }

    public function test_product_detail_includes_variant_stock(): void
    {
        $product = Product::create(['name' => 'Test', 'slug' => 'test', 'is_active' => 1]);
        $variant = ProductVariant::create(['product_id' => $product->id, 'label' => 'V1', 'price' => 100, 'is_active' => 1]);
        $warehouse = Warehouse::create(['name' => 'Gudang A']);
        WarehouseStock::create(['warehouse_id' => $warehouse->id, 'product_variant_id' => $variant->id, 'quantity' => 10]);
        WarehouseStock::create(['warehouse_id' => $warehouse->id, 'product_variant_id' => $variant->id, 'quantity' => 5]);

        $response = $this->getJson("/api/products/{$product->slug}");

        $response->assertStatus(200);
        $this->assertEquals(15, $response->json('data.variants.0.stock'));
    }

    public function test_product_detail_includes_gallery_images_sorted(): void
    {
        $product = Product::create(['name' => 'Test', 'slug' => 'test', 'is_active' => 1]);

        ProductImage::create(['product_id' => $product->id, 'sort_order' => 3, 'path' => null]);
        ProductImage::create(['product_id' => $product->id, 'sort_order' => 1, 'path' => null]);
        ProductImage::create(['product_id' => $product->id, 'sort_order' => 2, 'path' => null]);

        $response = $this->getJson("/api/products/{$product->slug}");

        $response->assertStatus(200);
        $images = $response->json('data.images');
        $this->assertEquals(1, $images[0]['sort_order']);
        $this->assertEquals(2, $images[1]['sort_order']);
        $this->assertEquals(3, $images[2]['sort_order']);
    }

    public function test_product_detail_includes_active_promotion_price(): void
    {
        $product = Product::create(['name' => 'Test', 'slug' => 'test', 'is_active' => 1]);
        $variant = ProductVariant::create([
            'product_id' => $product->id,
            'label' => 'V1',
            'price' => 50000,
            'is_active' => 1,
        ]);

        $promotion = Promotion::create([
            'name' => 'Diskon',
            'is_active' => 1,
            'start_at' => now()->subDay(),
            'end_at' => now()->addDay(),
        ]);

        PromotionItem::create([
            'promotion_id' => $promotion->id,
            'product_variant_id' => $variant->id,
            'override_price' => 40000,
        ]);

        $response = $this->getJson("/api/products/{$product->slug}");

        $response->assertStatus(200);
        $this->assertEquals(40000, (int) $response->json('data.variants.0.promo_price'));
    }

    public function test_product_detail_returns_404_for_inactive_product(): void
    {
        $product = Product::create(['name' => 'Inactive', 'slug' => 'inactive', 'is_active' => 0]);

        $response = $this->getJson("/api/products/{$product->slug}");

        $response->assertStatus(404);
    }

    public function test_product_detail_returns_404_for_non_existent_slug(): void
    {
        $response = $this->getJson('/api/products/nonexistent');

        $response->assertStatus(404);
    }
}
