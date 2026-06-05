<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderDetailResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'order_number' => $this->order_number,
            'status' => $this->status,
            'status_label' => $this->statusLabel(),
            'buyer_name' => $this->buyer_name,
            'buyer_email' => $this->buyer_email,
            'buyer_phone' => $this->buyer_phone,
            'shipping_address' => $this->shipping_address,
            'shipping_note' => $this->shipping_note,
            'subtotal' => (float) $this->subtotal,
            'shipping_cost' => (float) $this->shipping_cost,
            'point_redeemed' => (int) $this->point_redeemed,
            'point_earned' => (int) $this->point_earned,
            'total' => (float) $this->total,
            'paid_at' => $this->paid_at?->format('Y-m-d H:i:s'),
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at->format('Y-m-d H:i:s'),

            'items' => $this->orderItems->map(fn($item) => [
                'id' => $item->id,
                'product_name' => $item->product_name,
                'variant_label' => $item->variant_label,
                'unit_price' => (float) $item->unit_price,
                'quantity' => $item->quantity,
                'subtotal' => (float) $item->subtotal,
            ]),

            'payments' => $this->payments->map(fn($payment) => [
                'id' => $payment->id,
                'amount' => (float) $payment->amount,
                'status' => $payment->status,
                'status_label' => match($payment->status) {
                    1 => 'Menunggu Konfirmasi',
                    2 => 'Disetujui',
                    3 => 'Ditolak',
                    default => 'Unknown',
                },
                'rejected_reason' => $payment->status === 3 ? $payment->rejected_reason : null,
                'proof_url' => $payment->proofPath?->link,
                'bank_name' => $payment->paymentAccount?->bank_name,
                'account_number' => $payment->paymentAccount?->account_number,
                'account_name' => $payment->paymentAccount?->account_name,
                'created_at' => $payment->created_at->format('Y-m-d H:i:s'),
            ]),

            'shipment' => $this->shipment ? [
                'id' => $this->shipment->id,
                'status' => $this->shipment->status,
                'status_label' => $this->shipment->statusLabel(),
                'courier_name' => $this->shipment->courier_name,
                'tracking_number' => $this->shipment->tracking_number,
                'shipping_cost' => (float) $this->shipment->shipping_cost,
                'delivered_at' => $this->shipment->delivered_at?->format('Y-m-d H:i:s'),
                'warehouse' => $this->warehouse?->name,
                'tracking_logs' => $this->shipment->trackingLogs->map(fn($log) => [
                    'status' => $log->status,
                    'status_label' => $log->statusLabel(),
                    'note' => $log->note,
                    'location' => $log->location,
                    'created_at' => $log->created_at->format('Y-m-d H:i:s'),
                ]),
            ] : null,
        ];
    }
}
