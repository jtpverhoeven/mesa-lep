<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ChangeVetoResultRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('results.veto');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'parameter' => ['required', 'string', 'max:512'],
            'value' => [$this->isMethod('DELETE') ? 'exclude' : 'required', 'string', 'max:512'],
            'reason' => ['required', 'string', 'max:512'],
            'disposition' => ['nullable', Rule::in(['+', '-', '.'])],
        ];
    }

    public function messages(): array
    {
        return [
            'parameter.required' => 'Selecteer een resultaatveld.',
            'value.required' => 'Voer een vetoresultaat in.',
            'reason.required' => 'Een reden voor deze wijziging is verplicht.',
            'disposition.in' => 'Selecteer een geldig uitslagtype.',
        ];
    }
}
