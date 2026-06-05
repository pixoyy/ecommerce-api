<?php

namespace App\Services;

use App\Models\Order;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class OrderService
{
    public function getUserOrders(int $userId, array $filters = []): LengthAwarePaginator
    {
        $query = Order::where('user_id', $userId)
            ->with(['orderItems', 'payments' => function ($q) {
                $q->latest();
            }]);

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        $sortField = $filters['sort'] ?? 'created_at';
        $sortDir = $filters['order'] ?? 'desc';
        $query->orderBy($sortField, $sortDir);

        return $query->paginate($filters['per_page'] ?? 10);
    }

    public function getOrderByNumber(int $userId, string $orderNumber): Order
    {
        return Order::where('order_number', $orderNumber)
            ->where('user_id', $userId)
            ->with([
                'orderItems',
                'payments.paymentAccount',
                'payments.proofPath',
                'shipment.trackingLogs' => function ($q) {
                    $q->orderBy('created_at', 'asc');
                },
                'warehouse',
            ])
            ->firstOrFail();
    }
}
