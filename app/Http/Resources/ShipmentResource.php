<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ShipmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'shipment' => $this->shipment ? [
                'id' => $this->shipment->id,
                'status' => $this->shipment->status,
                'status_label' => $this->shipment->statusLabel(),
                'courier_name' => $this->shipment->courier_name,
                'tracking_number' => $this->shipment->tracking_number,
                'shipping_cost' => (float) $this->shipment->shipping_cost,
                'warehouse' => $this->shipment->warehouse?->name,
                'delivered_at' => $this->shipment->delivered_at?->format('Y-m-d H:i:s'),
            ] : null,
            'tracking_logs' => $this->tracking_logs->map(fn($log) => [
                'id' => $log->id,
                'status' => $log->status,
                'status_label' => $log->statusLabel(),
                'note' => $log->note,
                'location' => $log->location,
                'created_at' => $log->created_at->format('Y-m-d H:i:s'),
            ]),
        ];
    }
}
