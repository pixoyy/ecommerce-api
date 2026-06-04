<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'order_number' => $this->order_number,
            'status' => $this->status,
            'status_label' => $this->statusLabel(),
            'subtotal' => (float) $this->subtotal,
            'shipping_cost' => (float) $this->shipping_cost,
            'point_redeemed' => (int) $this->point_redeemed,
            'point_earned' => (int) $this->point_earned,
            'total' => (float) $this->total,
            'items_count' => $this->orderItems->sum('quantity'),
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
        ];
    }
}
