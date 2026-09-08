<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveClientRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('clients.manage');
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:256'],
            'street_name' => ['nullable', 'string', 'max:128'],
            'street_number' => ['nullable', 'string', 'max:6'],
            'postal_code' => ['nullable', 'string', 'max:12'],
            'place' => ['nullable', 'string', 'max:128'],
            'country' => ['nullable', 'string', 'max:128'],
            'telephone' => ['nullable', 'string', 'max:32'],
            'cellphone' => ['nullable', 'string', 'max:32'],
            'email' => ['nullable', 'string', 'max:512'],
            'title' => ['nullable', 'string', 'max:12'],
            'fname' => ['nullable', 'string', 'max:128'],
            'mname' => ['nullable', 'string', 'max:128'],
            'lname' => ['nullable', 'string', 'max:128'],
            'nvwa_number' => ['nullable', 'string'],
            'debit_number' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
            'categories' => ['nullable', 'array'],
            'categories.*' => ['required', 'integer', 'distinct', Rule::exists('clientcategories', 'id')],
        ];
    }
}