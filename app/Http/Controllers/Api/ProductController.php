<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductResource;
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
}
