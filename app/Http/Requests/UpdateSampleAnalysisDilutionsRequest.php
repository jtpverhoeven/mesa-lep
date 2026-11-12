<?php

namespace App\Http\Requests;

use App\Rules\SequentialDilutions;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateSampleAnalysisDilutionsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('dilutions.update') === true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'dilutions' => ['required', 'array', 'min:1', new SequentialDilutions('value')],
            'dilutions.*.value' => ['required', 'numeric'],
            'dilutions.*.replicates' => ['nullable', 'integer', 'min:0', 'max:99'],
        ];
    }

    public function messages(): array
    {
        return [
            'dilutions.required' => 'Kies minimaal één verdunning.',
            'dilutions.min' => 'Kies minimaal één verdunning.',
            'dilutions.*.value.required' => 'Elke verdunning heeft een waarde nodig.',
            'dilutions.*.value.numeric' => 'Een verdunning moet een getal zijn.',
            'dilutions.*.replicates.*' => 'Het aantal replica\'s moet een geheel getal tussen 0 en 99 zijn.',
        ];
    }
}
