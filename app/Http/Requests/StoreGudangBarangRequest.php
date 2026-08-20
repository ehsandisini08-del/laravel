<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreGudangBarangRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:50', 'unique:items,code'],
            'name' => ['required', 'string', 'max:200'],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'unit' => ['required', 'string', 'max:30'],
            'description' => ['nullable', 'string', 'max:1000'],
            'min_stock' => ['required', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'code.required' => 'Kode Barang wajib diisi.',
            'code.unique' => 'Kode Barang sudah digunakan.',
            'code.max' => 'Kode Barang maksimal 50 karakter.',
            'name.required' => 'Nama Barang wajib diisi.',
            'name.max' => 'Nama Barang maksimal 200 karakter.',
            'category_id.exists' => 'Kategori tidak valid.',
            'unit.required' => 'Satuan wajib diisi.',
            'min_stock.required' => 'Stok minimum wajib diisi.',
            'min_stock.min' => 'Stok minimum tidak boleh negatif.',
        ];
    }
}
