<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $priceRange = $this->getPriceRangeAttribute();
        $promoPrice = $this->getActivePromoPrice();

        $genderLabels = ['Unisex', 'Pria', 'Wanita'];

        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'thumbnail' => $this->thumbnailImage?->link,
            'category' => $this->category?->name,
            'brand' => $this->brand?->name,
            'gender' => $this->gender,
            'gender_label' => $genderLabels[$this->gender] ?? 'Unisex',
            'min_price' => $priceRange['min'],
            'max_price' => $priceRange['max'],
            'has_active_promotion' => !is_null($promoPrice),
            'promo_price' => $promoPrice,
            'average_rating' => round($this->reviews_avg_rating ?? 0, 1),
            'review_count' => $this->reviews_count ?? $this->reviews()->where('is_visible', 1)->count(),
            'is_active' => $this->is_active,
        ];
    }
}
