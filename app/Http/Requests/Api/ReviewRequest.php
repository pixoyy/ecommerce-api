<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class ReviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'product_variant_id' => 'required|exists:product_variants,id',
            'rating' => 'required|integer|min:1|max:5',
            'review' => 'nullable|string|max:1000',
        ];
    }

    public function messages(): array
    {
        return [
            'product_variant_id.required' => 'Varian produk wajib dipilih',
            'product_variant_id.exists' => 'Varian produk tidak ditemukan',
            'rating.required' => 'Rating wajib diisi',
            'rating.min' => 'Rating minimal 1',
            'rating.max' => 'Rating maksimal 5',
            'review.max' => 'Ulasan maksimal 1000 karakter',
        ];
    }
}
