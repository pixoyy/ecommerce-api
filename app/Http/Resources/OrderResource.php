<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $latestPayment = $this->payments->first();
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
            'payment_status' => $latestPayment?->status,
            'payment_status_label' => match($latestPayment?->status) {
                1 => 'Menunggu Konfirmasi',
                2 => 'Lunas',
                3 => 'Ditolak',
                default => 'Belum Dibayar',
            },
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
        ];
    }
}
