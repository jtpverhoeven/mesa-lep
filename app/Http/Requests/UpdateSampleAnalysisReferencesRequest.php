<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateSampleAnalysisReferencesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('samples.update-research') === true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'reference' => ['present', 'array'],
            'reference_source' => ['present', 'nullable', 'integer'],
        ];
    }
}
