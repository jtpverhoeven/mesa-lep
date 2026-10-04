<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SyncPortalAssayClientsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('settings.advanced') ?? false;
    }

    public function rules(): array
    {
        return [
            'client_ids' => ['nullable', 'array'],
            'client_ids.*' => ['integer', 'distinct', Rule::exists('clients', 'id')],
            'replace_all' => ['required', 'boolean'],
        ];
    }
}
