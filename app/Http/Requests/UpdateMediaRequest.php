<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateMediaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('administrator') ?? false;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:65535'],
            'short_name' => ['nullable', 'string', 'max:32'],
            'confirmation_media' => ['required', 'boolean'],
            'type' => ['required', 'integer', 'in:1,2,3'],
            'supplements_txt' => ['nullable', 'string'],
            'hasDate' => ['required', 'boolean'],
            'acceptable_range' => ['nullable', 'string', 'max:255'],
            'used_for_prediction' => ['sometimes', 'boolean'],
            'prediction_default_quant' => ['sometimes', 'integer', 'min:0'],
            'conf_enabled_pos' => ['sometimes', 'boolean'],
            'conf_enabled_neg' => ['sometimes', 'boolean'],
            'conf_enabled_blank' => ['sometimes', 'boolean'],
        ];
    }
}