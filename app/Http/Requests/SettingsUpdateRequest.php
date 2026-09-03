<?php

namespace App\Http\Requests;

use App\Services\SettingService;
use Illuminate\Foundation\Http\FormRequest;

class SettingsUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return app(SettingService::class)->rules();
    }

    public function messages(): array
    {
        return [
            'app_name.required' => 'Nama Aplikasi wajib diisi.',
            'timezone.required' => 'Zona Waktu wajib diisi.',
            'locale.required' => 'Bahasa wajib diisi.',
            'pagination.min' => 'Data per halaman minimal 5.',
            'pagination.max' => 'Data per halaman maksimal 100.',
            'default_due_day.min' => 'Hari jatuh tempo minimal 1.',
            'default_due_day.max' => 'Hari jatuh tempo maksimal 31.',
            'default_isolation_day.min' => 'Hari isolir minimal 1.',
            'default_isolation_day.max' => 'Hari isolir maksimal 31.',
            'mail_port.max' => 'Port SMTP tidak valid.',
            'log_retention_days.max' => 'Retensi log maksimal 365 hari.',
            'company_logo.image' => 'File logo harus berupa gambar.',
            'company_logo.mimes' => 'Format logo harus berupa JPG, JPEG, PNG, WEBP, atau SVG.',
            'company_logo.max' => 'Ukuran file logo maksimal 2MB.',
        ];
    }
}
