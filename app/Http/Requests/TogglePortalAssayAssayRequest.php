<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TogglePortalAssayAssayRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('settings.advanced') ?? false;
    }

    public function rules(): array
    {
        return [
            'assay_id' => ['required', 'integer', Rule::exists('assays', 'id')],
            'attached' => ['required', 'boolean'],
        ];
    }
}
