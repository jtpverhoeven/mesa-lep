<?php

namespace App\Http\Requests;

use App\Models\Assay;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAssayFieldRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('viewAny', Assay::class);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:64', Rule::unique('assayfields', 'name')->where('active', 1)],
            'has_default_value' => ['required', 'boolean'],
            'standard_value' => ['nullable', 'string', 'max:64'],
            'position' => ['required', 'integer', 'min:0'],
        ];
    }
}