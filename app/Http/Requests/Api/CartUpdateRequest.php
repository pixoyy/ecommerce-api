<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class CartUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'quantity' => 'required|integer|min:1|max:100',
        ];
    }

    public function messages(): array
    {
        return [
            'quantity.required' => 'Quantity wajib diisi',
            'quantity.integer' => 'Quantity harus berupa angka',
            'quantity.min' => 'Minimal quantity 1',
            'quantity.max' => 'Maksimal quantity 100',
        ];
    }
}
