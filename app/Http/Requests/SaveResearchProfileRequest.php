<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveResearchProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('research-profiles.manage') === true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:128'],
            'global' => ['required', 'boolean'],
            'client' => ['nullable', 'integer', Rule::exists('clients', 'id')->where('active', 1), 'required_if:global,0'],
            'portal_visible' => ['required', 'boolean'],
            'lims_visible' => ['required', 'boolean'],
            'assays' => ['array'],
            'assays.*.assay' => ['required', 'integer', Rule::exists('assays', 'id')],
            'assays.*.dillutions' => ['nullable', 'array'],
            'assays.*.replicates' => ['required', 'integer', 'min:0'],
            'assays.*.reference' => ['nullable', 'array'],
            'assays.*.conf_trip' => ['required', 'integer', 'min:0'],
            'assays.*.reference_source' => ['nullable', 'integer', Rule::exists('referencesources', 'id')],
        ];
    }
}