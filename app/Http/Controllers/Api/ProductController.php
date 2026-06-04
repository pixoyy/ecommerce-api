<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductDetailResource;
use App\Http\Resources\ProductResource;
use App\Http\Resources\ReviewResource;
use App\Models\Product;
use App\Services\ProductService;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    use ApiResponseTrait;

    public function __construct(
        protected ProductService $productService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $filters = $request->only(['category', 'brand', 'gender', 'search', 'price_min', 'price_max', 'sort']);
        $products = $this->productService->getProducts($filters);

        return $this->success(
            ProductResource::collection($products),
            'Daftar produk berhasil diambil'
        );
    }

    public function show(string $slug): JsonResponse
    {
        $product = $this->productService->getProductBySlug($slug);

        return $this->success(
            new ProductDetailResource($product),
            'Detail produk berhasil diambil'
        );
    }

    public function reviews(Product $product): JsonResponse
    {
        if (!$product->is_active) {
            return $this->error('Produk tidak ditemukan', 404);
        }

        $reviews = $this->productService->getProductReviews($product->id);

        return $this->success(
            ReviewResource::collection($reviews),
            'Ulasan produk berhasil diambil'
        );
    }
}
