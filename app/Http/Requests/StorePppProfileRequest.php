<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePppProfileRequest extends FormRequest
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
            'local_address' => ['nullable', 'string', 'max:255'],
            'remote_address' => ['nullable', 'string', 'max:255'],
            'dns_server' => ['nullable', 'string', 'max:255'],
            'rate_limit' => ['nullable', 'string', 'max:255'],
            'parent_queue' => ['nullable', 'string', 'max:255'],
            'only_one' => ['nullable', 'boolean'],
            'change_tcp_mss' => ['nullable', 'boolean'],
            'use_compression' => ['nullable', 'boolean'],
            'use_encryption' => ['nullable', 'boolean'],
            'use_ipv6' => ['nullable', 'boolean'],
            'bridge' => ['nullable', 'string', 'max:255'],
            'bridge_path_cost' => ['nullable', 'integer', 'min:1', 'max:999'],
            'bridge_horizon' => ['nullable', 'string', 'max:255'],
            'comment' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'router_id.required' => 'Router is required',
            'router_id.exists' => 'Selected router does not exist',
            'name.required' => 'Profile name is required',
            'name.max' => 'Profile name must not exceed 255 characters',
        ];
    }
}
