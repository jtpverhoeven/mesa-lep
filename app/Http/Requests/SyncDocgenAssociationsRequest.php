<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SyncDocgenAssociationsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('settings.advanced') ?? false;
    }

    public function rules(): array
    {
        return [
            'show_hidden' => ['required', 'boolean'],
            'assay_ids' => ['present', 'array'],
            'assay_ids.*' => [
                'integer', 'distinct',
                Rule::exists('assays', 'id')->when(
                    ! $this->boolean('show_hidden'),
                    fn ($rule) => $rule->where('active', 1),
                ),
            ],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['assay_ids' => $this->input('assay_ids', [])]);
    }
}
