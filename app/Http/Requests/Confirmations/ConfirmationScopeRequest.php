<?php

namespace App\Http\Requests\Confirmations;

use Illuminate\Foundation\Http\FormRequest;

abstract class ConfirmationScopeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('samples.update') ?? false;
    }

    protected function scopeRules(): array
    {
        return [
            'df' => ['required', 'string', 'max:32', 'regex:/^(global|\d+(?:\.\d+)?)$/'],
            'rep' => ['required', 'integer', 'min:0'],
        ];
    }
}
