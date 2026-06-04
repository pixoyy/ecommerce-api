<?php

namespace Tests\Feature\Api\Catalog;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Testing\Traits\CreatesBusinessSchema;
use Tests\TestCase;

class ProductTest extends TestCase
{
    use CreatesBusinessSchema;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpBusinessSchema();
    }

    public function test_can_list_products_with_pagination(): void
    {
        Product::create([
            'name' => 'Test Product',
            'slug' => 'test-product',
            'is_active' => 1,
        ]);

        $response = $this->getJson('/api/products');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    '*' => ['id', 'name', 'slug', 'thumbnail', 'category', 'brand', 'gender', 'gender_label', 'min_price', 'max_price', 'has_active_promotion', 'promo_price', 'average_rating', 'review_count', 'is_active'],
                ],
                'meta' => ['current_page', 'last_page', 'per_page', 'total'],
            ]);
    }

    public function test_can_filter_products_by_category(): void
    {
        $category = Category::create(['name' => 'Elektronik', 'slug' => 'elektronik', 'is_active' => 1]);
        $otherCategory = Category::create(['name' => 'Fashion', 'slug' => 'fashion', 'is_active' => 1]);

        Product::create(['name' => 'Product A', 'slug' => 'product-a', 'category_id' => $category->id, 'is_active' => 1]);
        Product::create(['name' => 'Product B', 'slug' => 'product-b', 'category_id' => $otherCategory->id, 'is_active' => 1]);

        $response = $this->getJson('/api/products?category=elektronik');

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data'));
        $this->assertEquals('product-a', $response->json('data.0.slug'));
    }

    public function test_can_filter_products_by_brand(): void
    {
        $brand = Brand::create(['name' => 'Nike', 'slug' => 'nike']);
        $otherBrand = Brand::create(['name' => 'Adidas', 'slug' => 'adidas']);

        Product::create(['name' => 'Product A', 'slug' => 'product-a', 'brand_id' => $brand->id, 'is_active' => 1]);
        Product::create(['name' => 'Product B', 'slug' => 'product-b', 'brand_id' => $otherBrand->id, 'is_active' => 1]);

        $response = $this->getJson('/api/products?brand=nike');

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data'));
        $this->assertEquals('product-a', $response->json('data.0.slug'));
    }

    public function test_can_filter_products_by_gender(): void
    {
        Product::create(['name' => 'Pria Product', 'slug' => 'pria-product', 'gender' => 1, 'is_active' => 1]);
        Product::create(['name' => 'Wanita Product', 'slug' => 'wanita-product', 'gender' => 2, 'is_active' => 1]);

        $response = $this->getJson('/api/products?gender=1');

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data'));
        $this->assertEquals('pria-product', $response->json('data.0.slug'));
    }

    public function test_can_search_products(): void
    {
        Product::create(['name' => 'Baju Batik', 'slug' => 'baju-batik', 'description' => 'Batik modern', 'is_active' => 1]);
        Product::create(['name' => 'Celana Jeans', 'slug' => 'celana-jeans', 'description' => 'Celana denim', 'is_active' => 1]);

        $response = $this->getJson('/api/products?search=batik');

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data'));
        $this->assertEquals('baju-batik', $response->json('data.0.slug'));
    }

    public function test_can_filter_by_price_range(): void
    {
        $productA = Product::create(['name' => 'Produk Murah', 'slug' => 'produk-murah', 'is_active' => 1]);
        $productB = Product::create(['name' => 'Produk Mahal', 'slug' => 'produk-mahal', 'is_active' => 1]);

        ProductVariant::create(['product_id' => $productA->id, 'price' => 15000, 'is_active' => 1]);
        ProductVariant::create(['product_id' => $productB->id, 'price' => 75000, 'is_active' => 1]);

        $response = $this->getJson('/api/products?price_min=10000&price_max=50000');

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data'));
        $this->assertEquals('produk-murah', $response->json('data.0.slug'));
    }

    public function test_can_sort_products_by_price(): void
    {
        $murah = Product::create(['name' => 'Produk Murah', 'slug' => 'produk-murah', 'is_active' => 1]);
        $mahal = Product::create(['name' => 'Produk Mahal', 'slug' => 'produk-mahal', 'is_active' => 1]);

        ProductVariant::create(['product_id' => $murah->id, 'price' => 15000, 'is_active' => 1]);
        ProductVariant::create(['product_id' => $mahal->id, 'price' => 75000, 'is_active' => 1]);

        $ascResponse = $this->getJson('/api/products?sort=price_asc');
        $ascResponse->assertStatus(200);
        $this->assertEquals('produk-murah', $ascResponse->json('data.0.slug'));

        $descResponse = $this->getJson('/api/products?sort=price_desc');
        $descResponse->assertStatus(200);
        $this->assertEquals('produk-mahal', $descResponse->json('data.0.slug'));
    }

    public function test_inactive_products_not_shown(): void
    {
        Product::create(['name' => 'Active Product', 'slug' => 'active-product', 'is_active' => 1]);
        Product::create(['name' => 'Inactive Product', 'slug' => 'inactive-product', 'is_active' => 0]);

        $response = $this->getJson('/api/products');

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data'));
        $this->assertEquals('active-product', $response->json('data.0.slug'));
    }
}
