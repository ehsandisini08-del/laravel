<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePppProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
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
}
