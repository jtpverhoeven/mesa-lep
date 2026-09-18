<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePortalConnectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('settings.advanced') ?? false;
    }

    public function rules(): array
    {
        return [
            'acceptType' => ['required', 'string', 'max:128'],
            'bearer' => ['required', 'string'],
        ];
    }
}
