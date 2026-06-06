<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ShipmentResource;
use App\Models\Order;
use App\Services\ShipmentService;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;

/**
 * @group Shipment
 *
 * Order shipment tracking.
 *
 * @authenticated
 */
class ShipmentController extends Controller
{
    use ApiResponseTrait;

    public function __construct(
        protected ShipmentService $shipmentService
    ) {}

    /**
     * Get shipment tracking
     *
     * View shipment and tracking logs for an order.
     *
     * @urlParam order integer required The order ID. Example: 1
     */
    public function tracking(Order $order): JsonResponse
    {
        if ($order->user_id !== auth()->id()) {
            return $this->error('Pesanan tidak ditemukan', 404);
        }

        $result = $this->shipmentService->getTracking(auth()->id(), $order->id);

        return $this->success(
            new ShipmentResource((object) $result),
            'Data pengiriman berhasil diambil'
        );
    }
}
