<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveSampleProcedureRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('sampling-procedures.manage');
    }

    public function rules(): array
    {
        $procedure = $this->route('sampleProcedure');

        return [
            'name' => ['required', 'string', 'max:128', Rule::unique('sampleprocedures', 'name')->ignore($procedure)],
            'hide' => ['required', 'boolean'],
            'fields' => ['nullable', 'array'],
            'fields.*' => ['nullable', 'string'],
        ];
    }
}