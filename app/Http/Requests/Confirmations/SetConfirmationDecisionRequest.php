<?php

namespace App\Http\Requests\Confirmations;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SetConfirmationDecisionRequest extends FormRequest
{
    public function authorize(): bool
    {
        if (! $this->user()?->can('samples.update')) {
            return false;
        }

        return $this->input('decision') !== 'reset' || $this->user()->can('confirmations.reset');
    }

    public function rules(): array
    {
        return ['decision' => ['required', Rule::in(['enable', 'disable', 'reset'])]];
    }
}
