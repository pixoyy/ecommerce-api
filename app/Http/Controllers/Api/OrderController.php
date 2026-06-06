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

/**
 * @group Orders
 *
 * Order history, detail, and cancellation.
 *
 * @authenticated
 */
class OrderController extends Controller
{
    use ApiResponseTrait;

    public function __construct(
        protected OrderService $orderService
    ) {}

    /**
     * List orders
     *
     * Get paginated list of the authenticated user's orders.
     *
     * @queryParam status integer Filter by status (1=Pending, 2=Processing, 3=Shipped, 4=Delivered, 5=Cancelled). Example: 1
     * @queryParam sort string Sort field. Example: created_at
     * @queryParam order string Sort direction (asc, desc). Example: desc
     * @queryParam per_page integer Items per page. Example: 15
     */
    public function index(Request $request): JsonResponse
    {
        $filters = $request->only(['status', 'sort', 'order', 'per_page']);
        $orders = $this->orderService->getUserOrders(auth()->id(), $filters);

        return $this->success(
            OrderResource::collection($orders),
            'Daftar pesanan berhasil diambil'
        );
    }

    /**
     * Get order detail
     *
     * Show full order details including items, payments, and shipment tracking.
     *
     * @urlParam order_number string required The order number. Example: INV/20250101/00001
     */
    public function show(string $orderNumber): JsonResponse
    {
        $order = $this->orderService->getOrderByNumber(auth()->id(), $orderNumber);

        return $this->success(
            new OrderDetailResource($order),
            'Detail pesanan berhasil diambil'
        );
    }

    /**
     * Cancel order
     *
     * Cancel a pending or processing order. Restores stock and redeemed points.
     *
     * @urlParam order integer required The order ID. Example: 1
     */
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
