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
            'project_name' => ['required', 'string', 'max:128'],
            'project_custom_fields' => ['nullable', 'array'],
            'description' => ['required', 'string'],
            'sampling_method' => ['required', 'integer', Rule::exists('sampleprocedures', 'id')],
            'stored_in' => ['nullable', 'string', 'max:5'],
            'sample_note' => ['nullable', 'string'],
            'custom_fields' => ['nullable', 'array'],
        ];
    }
}