<?php

namespace App\Http\Requests\Confirmations;

class UpdateConfirmationNoteRequest extends ConfirmationScopeRequest
{
    public function rules(): array
    {
        return [
            ...$this->scopeRules(),
            'note' => ['present', 'nullable', 'string', 'max:65535'],
        ];
    }
}
