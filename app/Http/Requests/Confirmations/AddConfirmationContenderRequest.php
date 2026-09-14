<?php

namespace App\Http\Requests\Confirmations;

class AddConfirmationContenderRequest extends ConfirmationScopeRequest
{
    public function rules(): array
    {
        return $this->scopeRules();
    }
}
