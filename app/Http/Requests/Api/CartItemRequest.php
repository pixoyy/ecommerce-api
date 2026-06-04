<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class CartItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'product_variant_id' => 'required|exists:product_variants,id',
            'quantity' => 'required|integer|min:1|max:100',
        ];
    }

    public function messages(): array
    {
        return [
            'product_variant_id.required' => 'Varian produk wajib dipilih',
            'product_variant_id.exists' => 'Varian produk tidak ditemukan',
            'quantity.required' => 'Quantity wajib diisi',
            'quantity.integer' => 'Quantity harus berupa angka',
            'quantity.min' => 'Minimal quantity 1',
            'quantity.max' => 'Maksimal quantity 100',
        ];
    }
}
