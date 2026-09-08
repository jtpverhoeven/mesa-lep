<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SaveSampleFieldRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('sample-fields.manage');
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:32'],
            'alias' => ['required', 'string', 'max:64'],
            'type' => ['required', 'in:text,date,textarea'],
            'std_value' => ['required', 'string'],
            'position' => ['required', 'integer'],
        ];
    }
}