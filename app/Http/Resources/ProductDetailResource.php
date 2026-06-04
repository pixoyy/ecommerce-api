<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductDetailResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $genderLabels = ['Unisex', 'Pria', 'Wanita'];

        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'features' => $this->features,
            'thumbnail' => $this->thumbnailImage?->link,
            'category' => new CategoryResource($this->category),
            'brand' => new BrandResource($this->brand),
            'gender' => $this->gender,
            'gender_label' => $genderLabels[$this->gender] ?? 'Unisex',
            'images' => $this->productImages->map(fn ($img) => [
                'id' => $img->id,
                'url' => $img->fileStorage?->link,
                'sort_order' => $img->sort_order,
            ]),
            'variants' => $this->productVariants->map(fn ($v) => [
                'id' => $v->id,
                'label' => $v->label,
                'sku' => $v->sku,
                'price' => (float) $v->price,
                'promo_price' => $this->getActivePromoPriceForVariant($v),
                'stock' => $v->warehouseStocks->sum('quantity'),
                'is_active' => $v->is_active,
            ]),
            'average_rating' => round((float) ($this->reviews_avg_rating ?? 0), 1),
            'total_reviews' => $this->total_reviews ?? 0,
            'is_active' => $this->is_active,
        ];
    }

    private function getActivePromoPriceForVariant($variant): ?float
    {
        $activePromo = $variant->promotionItems->first(function ($item) {
            return $item->promotion
                && $item->promotion->is_active
                && $item->promotion->start_at <= now()
                && $item->promotion->end_at >= now();
        });

        return $activePromo ? (float) $activePromo->override_price : null;
    }
}
