<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CartResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $variant = $this->productVariant;

        $activePromo = $variant->promotionItems->first(function ($item) {
            return $item->promotion
                && $item->promotion->is_active
                && $item->promotion->start_at <= now()
                && $item->promotion->end_at >= now();
        });

        $currentPrice = $activePromo
            ? (float) $activePromo->override_price
            : (float) $variant->price;

        $totalStock = $variant->warehouseStocks->sum('quantity');

        return [
            'id' => $this->id,
            'product_variant_id' => $variant->id,
            'product_name' => $variant->product?->name,
            'variant_label' => $variant->label,
            'product_slug' => $variant->product?->slug,
            'product_thumbnail' => $variant->product?->thumbnailImage?->link,
            'unit_price' => (float) $variant->price,
            'promo_price' => $activePromo ? (float) $activePromo->override_price : null,
            'current_price' => $currentPrice,
            'quantity' => $this->quantity,
            'subtotal' => $currentPrice * $this->quantity,
            'stock_available' => $totalStock,
            'is_stock_sufficient' => $totalStock >= $this->quantity,
        ];
    }
}
