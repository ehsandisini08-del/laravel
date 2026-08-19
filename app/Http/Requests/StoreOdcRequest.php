<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreOdcRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'kode_odc' => ['required', 'string', 'max:50', Rule::unique('odcs', 'kode_odc')],
            'nama_odc' => ['required', 'string', 'max:255'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
        ];
    }

    public function messages(): array
    {
        return [
            'kode_odc.required' => 'Kode ODC wajib diisi.',
            'kode_odc.unique' => 'Kode ODC sudah digunakan.',
            'nama_odc.required' => 'Nama ODC wajib diisi.',
            'latitude.between' => 'Latitude harus antara -90 dan 90.',
            'longitude.between' => 'Longitude harus antara -180 dan 180.',
        ];
    }
}
