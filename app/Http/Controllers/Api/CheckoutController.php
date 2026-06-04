<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\CheckoutRequest;
use App\Http\Resources\OrderResource;
use App\Services\CheckoutService;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;

class CheckoutController extends Controller
{
    use ApiResponseTrait;

    public function __construct(
        protected CheckoutService $checkoutService
    ) {}

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
