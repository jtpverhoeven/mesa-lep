<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SetDefaultEmailTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('settings.advanced') ?? false;
    }

    public function rules(): array
    {
        return [
            'defaultFor' => ['required', 'string', Rule::in(['reports', 'reports_with_files', 'for_files'])],
        ];
    }
}
