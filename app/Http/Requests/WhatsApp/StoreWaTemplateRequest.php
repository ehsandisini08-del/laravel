<?php

namespace App\Http\Requests\WhatsApp;

use Illuminate\Foundation\Http\FormRequest;

class StoreWaTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $templateId = $this->route('template');

        return [
            'name' => ['required', 'string', 'max:255', 'unique:wa_templates,name,'.$templateId],
            'category' => ['required', 'string', 'in:reminder,payment,broadcast,otp,custom'],
            'title' => ['nullable', 'string', 'max:255'],
            'content' => ['required', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Nama template wajib diisi.',
            'name.unique' => 'Nama template sudah digunakan.',
            'category.required' => 'Kategori wajib dipilih.',
            'category.in' => 'Kategori tidak valid.',
            'content.required' => 'Konten template wajib diisi.',
        ];
    }
}
