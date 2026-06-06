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

/**
 * @group Catalog
 *
 * Product listing, detail, and public reviews.
 */
class ProductController extends Controller
{
    use ApiResponseTrait;

    public function __construct(
        protected ProductService $productService
    ) {}

    /**
     * List products
     *
     * Get paginated list of active products with optional filters.
     *
     * @queryParam category string Filter by category slug. Example: elektronik
     * @queryParam brand string Filter by brand slug. Example: samsung
     * @queryParam gender integer Filter by gender (1=Pria, 2=Wanita). Example: 1
     * @queryParam search string Search by product name or description. Example: batik
     * @queryParam price_min integer Minimum price. Example: 10000
     * @queryParam price_max integer Maximum price. Example: 500000
     * @queryParam sort string Sort order (price_asc, price_desc, newest). Example: price_asc
     *
     * @unauthenticated
     */
    public function index(Request $request): JsonResponse
    {
        $filters = $request->only(['category', 'brand', 'gender', 'search', 'price_min', 'price_max', 'sort']);
        $products = $this->productService->getProducts($filters);

        return $this->success(
            ProductResource::collection($products),
            'Daftar produk berhasil diambil'
        );
    }

    /**
     * Get product detail
     *
     * Show product with variants, images, promotions, and average rating.
     *
     * @urlParam slug string required The product slug. Example: smartphone-galaxy
     *
     * @unauthenticated
     */
    public function show(string $slug): JsonResponse
    {
        $product = $this->productService->getProductBySlug($slug);

        return $this->success(
            new ProductDetailResource($product),
            'Detail produk berhasil diambil'
        );
    }

    /**
     * Get product reviews
     *
     * Show public reviews for a product.
     *
     * @urlParam product integer required The product ID. Example: 1
     *
     * @unauthenticated
     */
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
