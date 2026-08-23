<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CompleteRepairTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        $task = $this->route('task');

        return $task && $this->user()?->id === $task->taken_by_user_id;
    }

    public function rules(): array
    {
        return [
            'keterangan_teknisi' => ['required', 'string', 'max:2000'],
            'foto_bukti' => ['nullable', 'image', 'max:5120', 'mimes:jpg,jpeg,png'],
        ];
    }

    public function messages(): array
    {
        return [
            'keterangan_teknisi.required' => 'Keterangan penyelesaian wajib diisi.',
            'keterangan_teknisi.max' => 'Keterangan maksimal 2000 karakter.',
            'foto_bukti.image' => 'File harus berupa gambar.',
            'foto_bukti.max' => 'Ukuran foto maksimal 5MB.',
            'foto_bukti.mimes' => 'Format foto harus jpg, jpeg, atau png.',
        ];
    }
}
