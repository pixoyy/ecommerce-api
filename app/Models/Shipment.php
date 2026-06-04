<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'order_id', 'warehouse_id', 'shipping_cost', 'status',
    'courier_name', 'tracking_number', 'delivered_at',
])]
class Shipment extends Model
{
    use HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return [
            'shipping_cost' => 'decimal:2',
            'delivered_at' => 'datetime',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function trackingLogs(): HasMany
    {
        return $this->hasMany(ShipmentTrackingLog::class);
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
