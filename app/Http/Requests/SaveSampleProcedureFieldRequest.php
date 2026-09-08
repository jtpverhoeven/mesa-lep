<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveSampleProcedureFieldRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('sampling-procedure-fields.manage');
    }

    public function rules(): array
    {
        $field = $this->route('sampleProcedureField');

        return [
            'name' => ['required', 'string', 'max:32', Rule::unique('sampleprocedurefields', 'name')->ignore($field)],
            'alias' => ['required', 'string', 'max:128'],
            'position' => ['required', 'integer', 'min:0'],
        ];
    }
}