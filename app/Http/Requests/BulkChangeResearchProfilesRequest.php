<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BulkChangeResearchProfilesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('research-profiles.manage') === true;
    }

    public function rules(): array
    {
        return [
            'profiles' => ['required', 'array', 'min:1'],
            'profiles.*' => ['integer', Rule::exists('researchprofiles', 'id')->where('active', 1)],
            'mutation' => ['required', Rule::in(['add', 'remove', 'swap'])],
            'assay' => ['required', 'integer', Rule::exists('assays', 'id')],
            'replacement_assay' => ['nullable', 'required_if:mutation,swap', 'different:assay', Rule::exists('assays', 'id')],
            'settings' => ['nullable', 'required_if:mutation,add', 'array'],
            'settings.dillutions' => ['nullable', 'array'],
            'settings.replicates' => ['nullable', 'integer', 'min:0'],
            'settings.reference' => ['nullable', 'array'],
            'settings.conf_trip' => ['nullable', 'integer', 'min:0'],
            'settings.reference_source' => ['nullable', 'integer', Rule::exists('referencesources', 'id')->whereNull('client')],
        ];
    }
}