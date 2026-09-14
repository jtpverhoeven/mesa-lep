<?php

namespace App\Http\Requests\Confirmations;

class UpdateConfirmationMetadataRequest extends ConfirmationScopeRequest
{
    public function rules(): array
    {
        return [
            ...$this->scopeRules(),
            'key' => ['required', 'string', 'regex:/^\d+_(?:inzet|aflees|poscontrol|negcontrol|blankcontrol|tht)$/'],
            'value' => ['present', 'nullable', 'string', 'max:255'],
        ];
    }
}
