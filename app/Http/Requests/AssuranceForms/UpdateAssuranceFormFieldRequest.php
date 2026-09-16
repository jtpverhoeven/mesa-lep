<?php

namespace App\Http\Requests\AssuranceForms;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAssuranceFormFieldRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('assurance-form.view') ?? false;
    }

    public function rules(): array
    {
        return [
            'field_key' => ['required', 'string', 'regex:/^b[0-3]_[A-Za-z0-9_.-]+$/', 'max:128'],
            'value' => ['present', 'nullable', 'string'],
        ];
    }
}
