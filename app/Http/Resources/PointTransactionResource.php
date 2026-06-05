<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PointTransactionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'type_label' => $this->type === 1 ? 'Poin Masuk' : 'Poin Keluar',
            'amount' => (int) $this->amount,
            'description' => $this->description,
            'order_number' => $this->order?->order_number,
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
        ];
    }
}
