<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateOdpRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'odc_id' => ['required', 'exists:odcs,id'],
            'kode_odp' => ['required', 'string', 'max:50', Rule::unique('odps', 'kode_odp')->ignore($this->route('odp'))],
            'nama_odp' => ['required', 'string', 'max:255'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
        ];
    }

    public function messages(): array
    {
        return [
            'odc_id.required' => 'ODC wajib dipilih.',
            'odc_id.exists' => 'ODC yang dipilih tidak valid.',
            'kode_odp.required' => 'Kode ODP wajib diisi.',
            'kode_odp.unique' => 'Kode ODP sudah digunakan.',
            'nama_odp.required' => 'Nama ODP wajib diisi.',
            'latitude.between' => 'Latitude harus antara -90 dan 90.',
            'longitude.between' => 'Longitude harus antara -180 dan 180.',
        ];
    }
}
