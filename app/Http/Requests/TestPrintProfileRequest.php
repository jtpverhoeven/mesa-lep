<?php

namespace App\Http\Requests;

use App\Models\Printer;
use App\Models\Sample;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TestPrintProfileRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('labels.print-settings') ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'barcode' => ['required', 'string', 'max:32', Rule::exists(Sample::class, 'barcode')],
            'profile' => [Rule::excludeIf($this->routeIs('print-profiles.run')), 'required', 'string', 'max:65535'],
            'printer_override' => ['nullable', 'integer', 'min:1', Rule::exists(Printer::class, 'id')],
            'date_offset' => ['nullable', 'integer', 'between:-365,365'],
        ];
    }

    public function messages(): array
    {
        return ['barcode.exists' => 'Geen monster gevonden met deze barcode.'];
    }

    public function attributes(): array
    {
        return ['barcode' => 'barcode', 'profile' => 'printscript', 'printer_override' => 'printeroverride', 'date_offset' => 'datumverschuiving'];
    }
}
