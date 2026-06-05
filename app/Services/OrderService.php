<?php

namespace App\Services;

use App\Models\Order;
use App\Models\PointTransaction;
use App\Models\UserPoint;
use App\Models\Warehouse;
use App\Models\WarehouseStock;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

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

    public function cancelOrder(int $userId, int $orderId): Order
    {
        return DB::transaction(function () use ($userId, $orderId) {
            $order = Order::where('id', $orderId)
                ->where('user_id', $userId)
                ->with('orderItems')
                ->firstOrFail();

            if (!in_array($order->status, [Order::STATUS_PENDING, Order::STATUS_PROCESSING])) {
                throw new \Exception('Pesanan tidak dapat dibatalkan');
            }

            foreach ($order->orderItems as $item) {
                $stocks = WarehouseStock::where('product_variant_id', $item->product_variant_id)
                    ->lockForUpdate()
                    ->orderBy('quantity', 'asc')
                    ->get();

                if ($stocks->isNotEmpty()) {
                    $stock = $stocks->first();
                    $stock->increment('quantity', $item->quantity);
                } else {
                    $defaultWarehouse = Warehouse::where('is_active', 1)->first();
                    if ($defaultWarehouse) {
                        WarehouseStock::create([
                            'warehouse_id' => $defaultWarehouse->id,
                            'product_variant_id' => $item->product_variant_id,
                            'quantity' => $item->quantity,
                        ]);
                    }
                }
            }

            if ($order->point_redeemed > 0) {
                $userPoints = UserPoint::where('user_id', $userId)->lockForUpdate()->first();
                if ($userPoints) {
                    $userPoints->increment('balance', $order->point_redeemed);

                    PointTransaction::create([
                        'user_id' => $userId,
                        'order_id' => $order->id,
                        'type' => 1,
                        'amount' => $order->point_redeemed,
                        'description' => 'Pengembalian poin pembatalan pesanan ' . $order->order_number,
                    ]);
                }
            }

            $order->update(['status' => Order::STATUS_CANCELLED]);

            $order->load(['orderItems', 'payments', 'shipment']);

            return $order->fresh();
        });
    }
}
