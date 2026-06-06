<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\ReviewRequest;
use App\Http\Resources\ReviewResource;
use App\Models\Order;
use App\Services\ReviewService;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;

/**
 * @group Reviews
 *
 * Product reviews for completed orders.
 *
 * @authenticated
 */
class ReviewController extends Controller
{
    use ApiResponseTrait;

    public function __construct(
        protected ReviewService $reviewService
    ) {}

    /**
     * Submit product review
     *
     * Submit a rating and review for a product in a delivered order.
     *
     * @urlParam order integer required The order ID. Example: 1
     * @bodyParam product_variant_id integer required The product variant ID. Example: 1
     * @bodyParam rating integer required Rating 1-5. Example: 5
     * @bodyParam review string Optional review text. Example: Produk bagus sekali!
     */
    public function store(Order $order, ReviewRequest $request): JsonResponse
    {
        if ($order->user_id !== auth()->id()) {
            return $this->error('Pesanan tidak ditemukan', 404);
        }

        try {
            $review = $this->reviewService->createReview(
                auth()->id(),
                $order->id,
                $request->validated()
            );

            return $this->success(
                new ReviewResource($review->load('user')),
                'Ulasan berhasil dikirim',
                201
            );
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 422);
        }
    }
}
