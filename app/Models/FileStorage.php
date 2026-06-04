<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['link'])]
class FileStorage extends Model
{
    use HasFactory, SoftDeletes;

    public function categories(): HasMany
    {
        return $this->hasMany(Category::class, 'image');
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class, 'thumbnail');
    }

    public function productImages(): HasMany
    {
        return $this->hasMany(ProductImage::class, 'path');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class, 'proof_path');
    }
}
