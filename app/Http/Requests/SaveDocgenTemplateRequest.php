<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SaveDocgenTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('settings.advanced') ?? false;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:65535'],
            'folder' => ['required', 'string', 'max:128', 'regex:/\A[a-zA-Z0-9_-]+\z/'],
            'active' => ['required', 'boolean'],
            'view_order' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
