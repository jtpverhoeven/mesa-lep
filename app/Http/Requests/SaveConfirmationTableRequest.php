<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveConfirmationTableRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('media.manage') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:128',
                Rule::unique('confirmationtables', 'name')->ignore($this->route('confirmation_table')?->id),
            ],
            'html' => ['nullable', 'string', 'max:10000000'],
        ];
    }
}
