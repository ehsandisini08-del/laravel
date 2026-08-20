<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreBarangKeluarRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'recipient' => ['nullable', 'string', 'max:150'],
            'reason' => ['nullable', 'string', 'max:200'],
            'transaction_date' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.item_id' => ['required', 'integer', 'exists:items,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
        ];
    }

    public function messages(): array
    {
        return [
            'recipient.max' => 'Penerima maksimal 150 karakter.',
            'reason.max' => 'Alasan maksimal 200 karakter.',
            'transaction_date.required' => 'Tanggal wajib diisi.',
            'items.required' => 'Minimal satu barang wajib diisi.',
            'items.min' => 'Minimal satu barang wajib diisi.',
            'items.*.item_id.required' => 'Barang wajib dipilih.',
            'items.*.item_id.exists' => 'Barang tidak valid.',
            'items.*.quantity.required' => 'Jumlah wajib diisi.',
            'items.*.quantity.min' => 'Jumlah minimal 1.',
        ];
    }
}
