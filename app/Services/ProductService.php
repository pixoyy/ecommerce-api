<?php

namespace App\Services;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Review;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ProductService
{
    public function getCategories(): LengthAwarePaginator
    {
        return Category::where('is_active', 1)
            ->with('categoryImage')
            ->withCount('products')
            ->orderBy('sort_order')
            ->paginate(20);
    }

    public function getBrands(): LengthAwarePaginator
    {
        return Brand::withCount('products')
            ->orderBy('name')
            ->paginate(20);
    }

    public function getProducts(array $filters): LengthAwarePaginator
    {
        $query = Product::where('is_active', 1)
            ->with([
                'brand',
                'category',
                'thumbnailImage',
                'productVariants.promotionItems.promotion',
            ])
            ->withAvg(['reviews' => fn ($q) => $q->where('is_visible', 1)], 'rating')
            ->withCount(['reviews' => fn ($q) => $q->where('is_visible', 1)]);

        if (!empty($filters['category'])) {
            $query->whereHas('category', fn ($q) => $q->where('slug', $filters['category']));
        }

        if (!empty($filters['brand'])) {
            $query->whereHas('brand', fn ($q) => $q->where('slug', $filters['brand']));
        }

        if (isset($filters['gender']) && $filters['gender'] !== '') {
            $query->where('gender', $filters['gender']);
        }

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                    ->orWhere('description', 'LIKE', "%{$search}%");
            });
        }

        if (!empty($filters['price_min']) || !empty($filters['price_max'])) {
            $query->whereHas('productVariants', function ($q) use ($filters) {
                if (!empty($filters['price_min'])) {
                    $q->where('price', '>=', $filters['price_min']);
                }
                if (!empty($filters['price_max'])) {
                    $q->where('price', '<=', $filters['price_max']);
                }
            });
        }

        $sort = $filters['sort'] ?? 'latest';
        match ($sort) {
            'price_asc' => $query->orderBy(
                ProductVariant::select('price')
                    ->whereColumn('product_id', 'products.id')
                    ->where('is_active', 1)
                    ->orderBy('price')
                    ->limit(1),
                'asc'
            ),
            'price_desc' => $query->orderBy(
                ProductVariant::select('price')
                    ->whereColumn('product_id', 'products.id')
                    ->where('is_active', 1)
                    ->orderBy('price', 'desc')
                    ->limit(1),
                'desc'
            ),
            default => $query->orderBy('created_at', 'desc'),
        };

        return $query->paginate(12);
    }

    public function getProductBySlug(string $slug): Product
    {
        $product = Product::where('slug', $slug)
            ->where('is_active', 1)
            ->firstOrFail();

        $product->load([
            'brand',
            'category',
            'thumbnailImage',
            'productImages' => fn ($q) => $q->orderBy('sort_order'),
            'productImages.fileStorage',
            'productVariants' => fn ($q) => $q->where('is_active', 1),
            'productVariants.warehouseStocks',
            'productVariants.promotionItems.promotion' => fn ($q) => $q
                ->where('is_active', 1)
                ->where('start_at', '<=', now())
                ->where('end_at', '>=', now()),
        ]);

        $product->loadAvg(['reviews' => fn ($q) => $q->where('is_visible', 1)], 'rating');
        $product->loadCount(['reviews as total_reviews' => fn ($q) => $q->where('is_visible', 1)]);

        return $product;
    }

    public function getProductReviews(int $productId): LengthAwarePaginator
    {
        return Review::where('product_id', $productId)
            ->where('is_visible', 1)
            ->with('user')
            ->latest()
            ->paginate(10);
    }
}
