<?php

namespace App\Http\Requests;

use App\Enums\CustomerStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class StoreCustomerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'address' => ['required', 'string', 'max:1000'],
            'phone' => ['required', 'string', 'max:20', 'unique:customers,phone'],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'area_id' => ['required', 'exists:areas,id'],
            'router_id' => ['required', 'exists:routers,id'],
            'package_id' => ['required', 'exists:packages,id'],
            'ppp_username' => ['required', 'string', 'max:255', 'unique:customers,ppp_username'],
            'ppp_password' => ['required', 'string', 'max:255'],
            'installation_date' => ['required', 'date'],
            'due_day' => ['required', 'integer', 'min:1', 'max:31'],
            'isolation_day' => ['nullable', 'integer', 'between:1,31'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'status' => ['nullable', new Enum(CustomerStatus::class)],
            'portal_enabled' => ['nullable', 'boolean'],
            'create_ppp_secret' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Nama pelanggan wajib diisi.',
            'address.required' => 'Alamat wajib diisi.',
            'phone.required' => 'Nomor telepon wajib diisi.',
            'phone.unique' => 'Nomor telepon sudah digunakan.',
            'latitude.required' => 'Koordinat lokasi wajib diisi.',
            'longitude.required' => 'Koordinat lokasi wajib diisi.',
            'area_id.required' => 'Area wajib dipilih.',
            'router_id.required' => 'Router wajib dipilih.',
            'package_id.required' => 'Paket wajib dipilih.',
            'ppp_username.required' => 'PPP Username wajib diisi.',
            'ppp_username.unique' => 'PPP Username sudah digunakan.',
            'ppp_password.required' => 'PPP Password wajib diisi.',
            'installation_date.required' => 'Tanggal pemasangan wajib diisi.',
            'due_day.required' => 'Tanggal jatuh tempo wajib diisi.',
            'due_day.integer' => 'Tanggal jatuh tempo harus berupa angka 1-31.',
            'due_day.min' => 'Tanggal jatuh tempo minimal 1.',
            'due_day.max' => 'Tanggal jatuh tempo maksimal 31.',
        ];
    }
}
