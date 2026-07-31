<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePppSecretRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'router_id' => ['required', 'exists:routers,id'],
            'name' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string', 'max:255'],
            'service' => ['nullable', 'string', 'max:255'],
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
            'router_id.required' => 'Router is required',
            'router_id.exists' => 'Selected router does not exist',
            'name.required' => 'Username is required',
            'name.max' => 'Username must not exceed 255 characters',
            'password.required' => 'Password is required',
            'password.max' => 'Password must not exceed 255 characters',
        ];
    }
}
