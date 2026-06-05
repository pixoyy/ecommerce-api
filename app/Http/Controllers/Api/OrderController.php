<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\OrderDetailResource;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Services\OrderService;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    use ApiResponseTrait;

    public function __construct(
        protected OrderService $orderService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $filters = $request->only(['status', 'sort', 'order', 'per_page']);
        $orders = $this->orderService->getUserOrders(auth()->id(), $filters);

        return $this->success(
            OrderResource::collection($orders),
            'Daftar pesanan berhasil diambil'
        );
    }

    public function show(string $orderNumber): JsonResponse
    {
        $order = $this->orderService->getOrderByNumber(auth()->id(), $orderNumber);

        return $this->success(
            new OrderDetailResource($order),
            'Detail pesanan berhasil diambil'
        );
    }

    public function cancel(Order $order): JsonResponse
    {
        if ($order->user_id !== auth()->id()) {
            return $this->error('Pesanan tidak ditemukan', 404);
        }

        try {
            $order = $this->orderService->cancelOrder(auth()->id(), $order->id);

            return $this->success(
                new OrderDetailResource($order),
                'Pesanan berhasil dibatalkan'
            );
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 422);
        }
    }
}
