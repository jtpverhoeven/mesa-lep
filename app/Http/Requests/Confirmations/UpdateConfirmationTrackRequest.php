<?php

namespace App\Http\Requests\Confirmations;

use Illuminate\Validation\Rule;

class UpdateConfirmationTrackRequest extends ConfirmationScopeRequest
{
    public function rules(): array
    {
        return [
            ...$this->scopeRules(),
            'contender' => ['required', 'integer', 'min:0'],
            'step' => ['required', 'integer', 'min:0'],
            'value' => ['present', 'nullable', 'string', Rule::in(['', '+', '-', '?'])],
        ];
    }
}
