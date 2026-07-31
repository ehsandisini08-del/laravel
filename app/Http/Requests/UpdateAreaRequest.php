<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAreaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $area = $this->route('area');

        return [
            'code' => ['required', 'string', 'max:10', Rule::unique('areas', 'code')->ignore($area)],
            'name' => ['required', 'string', 'max:100', Rule::unique('areas', 'name')->ignore($area)],
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
