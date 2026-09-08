<?php

namespace App\Http\Requests;

use App\Models\Assay;
use App\Models\AssayType;
use App\Models\AssayTypeField;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAssayTypeFieldRequest extends FormRequest
{
    public function authorize(): bool
    {
        $assayType = $this->route('assayType');
        $field = $this->route('field');

        return $assayType instanceof AssayType
            && $field instanceof AssayTypeField
            && (int) $field->test_id === (int) $assayType->id
            && $this->user()->can('viewAny', Assay::class);
    }

    public function rules(): array
    {
        return [
            'alias' => ['required', 'string', 'max:64'],
            'pos' => ['required', 'integer', 'min:0'],
            'filter' => ['required', Rule::in([0, 1, 2, 3, 4])],
            'endresults_driver' => ['required', 'boolean'],
        ];
    }
}