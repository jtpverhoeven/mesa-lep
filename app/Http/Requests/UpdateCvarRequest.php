<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCvarRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('settings.advanced') ?? false;
    }

    public function rules(): array
    {
        return [
            'value' => ['nullable', 'string', 'max:1024'],
        ];
    }
}