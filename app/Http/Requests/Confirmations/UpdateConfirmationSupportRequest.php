<?php

namespace App\Http\Requests\Confirmations;

class UpdateConfirmationSupportRequest extends ConfirmationScopeRequest
{
    public function rules(): array
    {
        return [
            ...$this->scopeRules(),
            'media_id' => ['required', 'integer', 'min:1'],
            'active' => ['required', 'boolean'],
        ];
    }
}
