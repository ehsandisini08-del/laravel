<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreAreaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:10', 'unique:areas,code'],
            'name' => ['required', 'string', 'max:100', 'unique:areas,name'],
            'description' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'code.required' => 'Kode Area wajib diisi.',
            'code.unique' => 'Kode Area sudah digunakan.',
            'code.max' => 'Kode Area maksimal 10 karakter.',
            'name.required' => 'Nama Area wajib diisi.',
            'name.unique' => 'Nama Area sudah digunakan.',
            'name.max' => 'Nama Area maksimal 100 karakter.',
        ];
    }
}
