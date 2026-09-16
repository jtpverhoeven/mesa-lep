<?php

namespace App\Http\Requests\Confirmations;

use Illuminate\Foundation\Http\FormRequest;

class UpdateConfirmationAssuranceExplanationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('samples.view') ?? false;
    }

    public function rules(): array
    {
        return [
            'df' => ['required', 'string', 'max:32', 'regex:/^(global|\d+(?:\.\d+)?)$/'],
            'rep' => ['required', 'integer', 'min:0'],
            'key' => ['required', 'string', 'regex:/^\d+_tht$/'],
            'explanation' => ['present', 'nullable', 'string'],
        ];
    }
}
