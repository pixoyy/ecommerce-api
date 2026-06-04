<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\BrandResource;
use App\Services\ProductService;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;

class BrandController extends Controller
{
    use ApiResponseTrait;

    public function __construct(
        protected ProductService $productService
    ) {}

    public function __invoke(): JsonResponse
    {
        $brands = $this->productService->getBrands();

        return $this->success(
            BrandResource::collection($brands),
            'Daftar brand berhasil diambil'
        );
    }
}
