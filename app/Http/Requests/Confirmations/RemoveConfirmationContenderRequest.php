<?php

namespace App\Http\Requests\Confirmations;

class RemoveConfirmationContenderRequest extends ConfirmationScopeRequest
{
    public function rules(): array
    {
        return $this->scopeRules();
    }
}
