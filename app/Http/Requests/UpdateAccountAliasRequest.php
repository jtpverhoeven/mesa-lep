<?php

namespace App\Http\Requests;

use App\Rules\AvailableLogin;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

class UpdateAccountAliasRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'alias' => ['bail', 'present', 'nullable', 'string', 'max:255', new AvailableLogin($this->user()->id)],
        ];
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->input('alias'))) {
            $alias = Str::lower(trim($this->input('alias')));
            $this->merge(['alias' => $alias === '' ? null : $alias]);
        }
    }
}
