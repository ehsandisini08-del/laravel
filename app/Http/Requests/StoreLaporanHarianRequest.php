<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreLaporanHarianRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->canAccessTeknisi() ?? false;
    }

    public function rules(): array
    {
        return [
            'nama_customer' => ['required', 'string', 'max:255'],
            'no_telp' => ['required', 'string', 'max:20'],
            'alamat' => ['required', 'string', 'max:500'],
            'keterangan' => ['required', 'string', 'max:2000'],
            'keterangan_teknisi' => ['required', 'string', 'max:2000'],
            'taken_by_user_id' => ['required', 'exists:users,id'],
            'completed_at' => ['required', 'date'],
            'foto_bukti' => ['nullable', 'image', 'max:2048'],
        ];
    }

    public function messages(): array
    {
        return [
            'nama_customer.required' => 'Nama pelanggan wajib diisi.',
            'no_telp.required' => 'Nomor telepon wajib diisi.',
            'alamat.required' => 'Alamat wajib diisi.',
            'keterangan.required' => 'Kendala wajib diisi.',
            'keterangan_teknisi.required' => 'Keterangan penyelesaian wajib diisi.',
            'taken_by_user_id.required' => 'Teknisi wajib dipilih.',
            'completed_at.required' => 'Tanggal selesai wajib diisi.',
            'foto_bukti.image' => 'Foto harus berupa gambar.',
            'foto_bukti.max' => 'Foto maksimal 2MB.',
        ];
    }
}
