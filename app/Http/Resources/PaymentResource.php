<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'amount' => (float) $this->amount,
            'status' => $this->status,
            'status_label' => match ($this->status) {
                1 => 'Menunggu Konfirmasi',
                2 => 'Disetujui',
                3 => 'Ditolak',
                default => 'Unknown',
            },
            'rejected_reason' => $this->when($this->status === 3, $this->rejected_reason),
            'proof_url' => $this->proofPath?->link,
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
        ];
    }
}
