<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePackageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return ! $this->user()?->isAdminArea() && ! $this->user()?->isTeknisi();
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'price' => ['required', 'numeric', 'min:0'],
            'router_id' => ['required', 'exists:routers,id'],
            'ppp_profile_id' => ['required', 'exists:ppp_profiles,id'],
            'areas' => ['required', 'array', 'min:1'],
            'areas.*' => ['required', 'exists:areas,id'],
            'description' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Package name is required.',
            'price.required' => 'Price is required.',
            'price.numeric' => 'Price must be a valid number.',
            'router_id.required' => 'Please select a router.',
            'ppp_profile_id.required' => 'Please select a PPP profile.',
            'areas.required' => 'Please select at least one area.',
            'areas.min' => 'Please select at least one area.',
        ];
    }
}
