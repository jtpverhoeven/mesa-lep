<?php

namespace App\Http\Requests;

use App\Models\Printer;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveLabelDesignRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('labels.design') ?? false;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:128'],
            'zpl' => ['required', 'string', 'max:65535'],
            'default_sample' => ['required', 'boolean'],
            'default_analysis' => ['required', 'boolean'],
            'default_printer' => ['nullable', 'integer', Rule::exists(Printer::class, 'id')],
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'naam',
            'zpl' => 'ZPL-code',
            'default_sample' => 'standaard voor monsters',
            'default_analysis' => 'standaard voor analyses',
            'default_printer' => 'standaardprinter',
        ];
    }
}
