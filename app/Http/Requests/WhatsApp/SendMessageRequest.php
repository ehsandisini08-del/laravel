<?php

namespace App\Http\Requests\WhatsApp;

use Illuminate\Foundation\Http\FormRequest;

class SendMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'device_id' => ['required', 'exists:wa_devices,id'],
            'customer_id' => ['nullable', 'exists:customers,id'],
            'phone' => ['required', 'string', 'max:20'],
            'message' => ['required', 'string', 'max:5000'],
        ];
    }

    public function messages(): array
    {
        return [
            'device_id.required' => 'Device wajib dipilih.',
            'device_id.exists' => 'Device tidak ditemukan.',
            'phone.required' => 'Nomor telepon wajib diisi.',
            'message.required' => 'Pesan wajib diisi.',
        ];
    }
}
