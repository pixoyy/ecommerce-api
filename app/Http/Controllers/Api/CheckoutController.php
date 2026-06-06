<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\CheckoutRequest;
use App\Http\Resources\OrderResource;
use App\Services\CheckoutService;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;

/**
 * @group Checkout
 *
 * Create orders from cart items.
 *
 * @authenticated
 */
class CheckoutController extends Controller
{
    use ApiResponseTrait;

    public function __construct(
        protected CheckoutService $checkoutService
    ) {}

    /**
     * Checkout
     *
     * Create an order from the current cart. Validates stock, applies promotions,
     * redeems points if requested, deducts stock, clears cart, and grants earned points.
     *
     * @bodyParam shipping_address string required Shipping address. Example: Jl. Merdeka No. 1, Jakarta
     * @bodyParam shipping_note string Optional note for delivery. Example: Pagi hari
     * @bodyParam redeem_points integer Points to redeem. Example: 10000
     */
    public function __invoke(CheckoutRequest $request): JsonResponse
    {
        try {
            $order = $this->checkoutService->checkout(
                auth()->id(),
                $request->validated()
            );

            return $this->success(
                new OrderResource($order),
                'Checkout berhasil',
                201
            );
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 422);
        }
    }
}
