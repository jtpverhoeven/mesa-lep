<?php

namespace App\Http\Requests;

use App\Models\Printer;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePrinterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('settings.advanced') ?? false;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:128', Rule::unique('printers', 'name')],
            'adres' => ['required', 'string', 'max:45'],
            'port' => ['nullable', 'integer', 'between:0,65535'],
            'type' => ['required', 'integer', Rule::in(array_keys(Printer::TYPES))],
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'naam',
            'adres' => 'adres',
            'port' => 'poort',
            'type' => 'printertype',
        ];
    }
}
