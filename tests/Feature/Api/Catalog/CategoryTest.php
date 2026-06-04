<?php

namespace Tests\Feature\Api\Catalog;

use App\Models\Category;
use App\Testing\Traits\CreatesBusinessSchema;
use Tests\TestCase;

class CategoryTest extends TestCase
{
    use CreatesBusinessSchema;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpBusinessSchema();
    }

    public function test_can_list_active_categories(): void
    {
        Category::create([
            'name' => 'Elektronik',
            'slug' => 'elektronik',
            'sort_order' => 1,
            'is_active' => 1,
        ]);

        $response = $this->getJson('/api/categories');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    '*' => ['id', 'name', 'slug', 'image', 'sort_order', 'product_count'],
                ],
            ]);

        $this->assertCount(1, $response->json('data'));
    }

    public function test_categories_sorted_by_sort_order(): void
    {
        Category::create(['name' => 'Z Category', 'slug' => 'z-category', 'sort_order' => 3, 'is_active' => 1]);
        Category::create(['name' => 'A Category', 'slug' => 'a-category', 'sort_order' => 1, 'is_active' => 1]);
        Category::create(['name' => 'M Category', 'slug' => 'm-category', 'sort_order' => 2, 'is_active' => 1]);

        $response = $this->getJson('/api/categories');

        $response->assertStatus(200);
        $categories = $response->json('data');

        $this->assertEquals('a-category', $categories[0]['slug']);
        $this->assertEquals('m-category', $categories[1]['slug']);
        $this->assertEquals('z-category', $categories[2]['slug']);
    }

    public function test_inactive_categories_not_shown(): void
    {
        Category::create(['name' => 'Active', 'slug' => 'active', 'sort_order' => 1, 'is_active' => 1]);
        Category::create(['name' => 'Inactive', 'slug' => 'inactive', 'sort_order' => 2, 'is_active' => 0]);

        $response = $this->getJson('/api/categories');

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data'));
        $this->assertEquals('active', $response->json('data.0.slug'));
    }
}
