<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateTenantRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        $reserved = [
            't','login','logout','register','admin','api','www','mail','support','billing','account',
            'assets','css','js','images','storage'
        ];

        return [
            'name' => ['required', 'string', 'max:255'],
            'subdomain' => [
                'required',
                'string',
                'max:50',
                'regex:/^[a-z0-9]([a-z0-9-]{0,48}[a-z0-9])?$/i',
                Rule::notIn($reserved),
                Rule::unique('tenants', 'subdomain'),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'subdomain.regex' => 'Tenant key may contain letters, numbers, and hyphens (cannot start/end with a hyphen).',
            'subdomain.not_in' => 'That tenant key is reserved. Choose another one.',
            'subdomain.unique' => 'That tenant key is already taken.',
        ];
    }
}



