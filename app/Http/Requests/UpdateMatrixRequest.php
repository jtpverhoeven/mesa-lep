<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateMatrixRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('settings.advanced') ?? false;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:65535'],
        ];
    }
}