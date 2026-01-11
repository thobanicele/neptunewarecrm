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
            'www','admin','app','api','mail','ftp','localhost','support',
            'billing','account','accounts','cdn','static',
            // your platform/reserved subdomains:
            'crm','tenant','super'
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
            'subdomain.regex' => 'Subdomain may contain letters, numbers, and hyphens (cannot start/end with a hyphen).',
            'subdomain.unique' => 'That subdomain is already taken. Try another one.',
            'subdomain.not_in' => 'That subdomain is reserved. Choose another one.',
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('subdomain')) {
            $this->merge([
                'subdomain' => strtolower(trim($this->input('subdomain'))),
            ]);
        }
    }
}


