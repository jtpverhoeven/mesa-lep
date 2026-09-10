<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSampleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('samples.create');
    }

    public function rules(): array
    {
        return [
            'client' => ['required', 'integer', Rule::exists('clients', 'id')],
            'project' => ['nullable', 'integer', Rule::exists('projects', 'id')],
            'project_name' => ['nullable', 'string', 'max:128'],
            'project_custom_fields' => ['nullable', 'array'],
            'description' => ['required', 'string'],
            'sampling_method' => ['required', 'integer', Rule::exists('sampleprocedures', 'id')],
            'sample_note' => ['nullable', 'string'],
            'custom_fields' => ['nullable', 'array'],
            'analyses' => ['nullable', 'array'],
            'analyses.*.type' => ['required', Rule::in(['profile', 'assay'])],
            'analyses.*.profile_id' => ['required_if:analyses.*.type,profile', 'nullable', 'integer', Rule::exists('researchprofiles', 'id')],
            'analyses.*.excluded_assay_profile_ids' => ['nullable', 'array'],
            'analyses.*.excluded_assay_profile_ids.*' => ['integer', Rule::exists('assayprofiles', 'id')],
            'analyses.*.assay_id' => ['required_if:analyses.*.type,assay', 'nullable', 'integer', Rule::exists('assays', 'id')],
            'analyses.*.settings' => ['required_if:analyses.*.type,assay', 'nullable', 'array'],
            'analyses.*.settings.dillutions' => ['nullable', 'array'],
            'analyses.*.settings.replicates' => ['nullable', 'integer', 'min:0'],
            'analyses.*.settings.reference' => ['nullable', 'array'],
            'analyses.*.settings.reference_scope' => ['nullable', 'string', 'max:5'],
            'analyses.*.settings.reference_source' => ['nullable', 'integer', Rule::exists('referencesources', 'id')],
        ];
    }
}
