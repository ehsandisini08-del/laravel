<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePppSecretRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'password' => ['nullable', 'string', 'max:255'],
            'profile' => ['nullable', 'string', 'max:255'],
            'local_address' => ['nullable', 'string', 'max:255'],
            'remote_address' => ['nullable', 'string', 'max:255'],
            'caller_id' => ['nullable', 'string', 'max:255'],
            'comment' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'password.max' => 'Password must not exceed 255 characters',
            'profile.max' => 'Profile must not exceed 255 characters',
        ];
    }
}
