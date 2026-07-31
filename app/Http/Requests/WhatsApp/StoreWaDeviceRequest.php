<?php

namespace App\Http\Requests\WhatsApp;

use Illuminate\Foundation\Http\FormRequest;

class StoreWaDeviceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'device_name' => ['required', 'string', 'max:255'],
            'session_name' => ['required', 'string', 'max:255', 'unique:wa_devices,session_name'],
        ];
    }

    public function messages(): array
    {
        return [
            'device_name.required' => 'Nama device wajib diisi.',
            'session_name.required' => 'Session name wajib diisi.',
            'session_name.unique' => 'Session name sudah digunakan.',
        ];
    }
}
