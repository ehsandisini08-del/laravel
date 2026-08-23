<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreRepairTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->canManageTeknisiTasks() ?? false;
    }

    public function rules(): array
    {
        return [
            'customer_id' => ['required', 'exists:customers,id'],
            'keterangan' => ['required', 'string', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'customer_id.required' => 'Customer wajib dipilih.',
            'customer_id.exists' => 'Customer tidak ditemukan.',
            'keterangan.required' => 'Keterangan tugas wajib diisi.',
            'keterangan.max' => 'Keterangan maksimal 2000 karakter.',
        ];
    }
}
