<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Shipment;

class ShipmentService
{
    public function getTracking(int $userId, int $orderId): array
    {
        Order::where('id', $orderId)->where('user_id', $userId)->firstOrFail();

        $shipment = Shipment::where('order_id', $orderId)
            ->with(['trackingLogs' => function ($q) {
                $q->orderBy('created_at', 'asc');
            }, 'warehouse'])
            ->first();

        if (!$shipment) {
            return [
                'shipment' => null,
                'tracking_logs' => [],
            ];
        }

        return [
            'shipment' => $shipment,
            'tracking_logs' => $shipment->trackingLogs,
        ];
    }
}
