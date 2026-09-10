<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreReferenceSourceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('settings.advanced') ?? false;
    }

    public function rules(): array
    {
        return [
            'name_nl' => ['required', 'string', 'max:65535'],
            'name_en' => ['nullable', 'string', 'max:65535'],
            'client' => ['nullable', 'integer', Rule::exists('clients', 'id')],
        ];
    }
}