<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreMatrixRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('administrator') ?? false;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:65535'],
        ];
    }
}