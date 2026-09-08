<?php

namespace App\Http\Requests;

use App\Models\Assay;
use App\Models\AssayField;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAssayFieldRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('viewAny', Assay::class);
    }

    public function rules(): array
    {
        $assayField = $this->route('assayField');
        $assayFieldId = $assayField instanceof AssayField ? $assayField->getKey() : $assayField;

        return [
            'name' => [
                'required',
                'string',
                'max:64',
                Rule::unique('assayfields', 'name')->where('active', 1)->ignore($assayFieldId),
            ],
            'has_default_value' => ['required', 'boolean'],
            'standard_value' => ['nullable', 'string', 'max:64'],
            'position' => ['required', 'integer', 'min:0'],
        ];
    }
}