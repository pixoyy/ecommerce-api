<?php

namespace Tests\Feature\Api\Catalog;

use App\Models\Brand;
use App\Testing\Traits\CreatesBusinessSchema;
use Tests\TestCase;

class BrandTest extends TestCase
{
    use CreatesBusinessSchema;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpBusinessSchema();
    }

    public function test_can_list_brands(): void
    {
        Brand::create(['name' => 'Nike', 'slug' => 'nike']);

        $response = $this->getJson('/api/brands');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    '*' => ['id', 'name', 'slug', 'product_count'],
                ],
            ]);
    }

    public function test_brands_sorted_alphabetically(): void
    {
        Brand::create(['name' => 'Zara', 'slug' => 'zara']);
        Brand::create(['name' => 'Adidas', 'slug' => 'adidas']);
        Brand::create(['name' => 'Nike', 'slug' => 'nike']);

        $response = $this->getJson('/api/brands');

        $response->assertStatus(200);
        $brands = $response->json('data');

        $this->assertEquals('Adidas', $brands[0]['name']);
        $this->assertEquals('Nike', $brands[1]['name']);
        $this->assertEquals('Zara', $brands[2]['name']);
    }
}
