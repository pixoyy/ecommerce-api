<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CategoryResource;
use App\Services\ProductService;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;

class CategoryController extends Controller
{
    use ApiResponseTrait;

    public function __construct(
        protected ProductService $productService
    ) {}

    public function __invoke(): JsonResponse
    {
        $categories = $this->productService->getCategories();

        return $this->success(
            CategoryResource::collection($categories),
            'Daftar kategori berhasil diambil'
        );
    }
}
