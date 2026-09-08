<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveClientCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('clients.manage');
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', Rule::unique('clientcategories', 'name')],
        ];
    }
}