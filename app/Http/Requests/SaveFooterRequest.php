<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveFooterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('settings.advanced') ?? false;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:128', 'regex:/\A[a-zA-Z0-9_-]+\z/'],
            'lang' => ['required', Rule::in(['nl', 'en'])],
            'q' => ['required', 'boolean'],
            'data' => ['required', 'string', 'max:65535'],
        ];
    }
}
