<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['shipment_id', 'updated_by', 'status', 'note', 'location'])]
class ShipmentTrackingLog extends Model
{
    use HasFactory, SoftDeletes;

    public function shipment(): BelongsTo
    {
        return $this->belongsTo(Shipment::class);
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'updated_by');
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            1 => 'Dikemas',
            2 => 'Dikirim',
            3 => 'Dalam Perjalanan',
            4 => 'Selesai',
            default => 'Unknown',
        };
    }
}
