<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'brand_id', 'category_id', 'thumbnail',
    'name', 'slug', 'description', 'features',
    'gender', 'is_active',
])]
class Product extends Model
{
    use HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function thumbnailImage(): BelongsTo
    {
        return $this->belongsTo(FileStorage::class, 'thumbnail');
    }

    public function productImages(): HasMany
    {
        return $this->hasMany(ProductImage::class);
    }

    public function productVariants(): HasMany
    {
        return $this->hasMany(ProductVariant::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function getPriceRangeAttribute(): array
    {
        $prices = $this->productVariants->where('is_active', 1)->pluck('price');

        if ($prices->isEmpty()) {
            return ['min' => 0, 'max' => 0];
        }

        return [
            'min' => (float) $prices->min(),
            'max' => (float) $prices->max(),
        ];
    }

    public function getActivePromoPrice(): ?float
    {
        $now = now();

        $minPrice = $this->productVariants
            ->flatMap(fn ($variant) => $variant->promotionItems
                ->filter(fn ($item) => $item->promotion
                    && $item->promotion->is_active
                    && $item->promotion->start_at <= $now
                    && $item->promotion->end_at >= $now)
                ->pluck('override_price'))
            ->min();

        return $minPrice ? (float) $minPrice : null;
    }
}
