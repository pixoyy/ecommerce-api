<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class PaymentUploadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'proof' => 'required|image|mimes:jpg,jpeg,png|max:2048',
            'amount' => 'required|numeric|min:0',
        ];
    }

    public function messages(): array
    {
        return [
            'proof.required' => 'Bukti transfer wajib diupload',
            'proof.image' => 'File harus berupa gambar',
            'proof.mimes' => 'Format gambar harus jpg/jpeg/png',
            'proof.max' => 'Ukuran file maksimal 2MB',
            'amount.required' => 'Jumlah pembayaran wajib diisi',
            'amount.numeric' => 'Jumlah pembayaran harus berupa angka',
        ];
    }
}
