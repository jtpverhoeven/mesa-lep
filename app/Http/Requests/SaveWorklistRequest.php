<?php

namespace App\Http\Requests;

use App\Models\Worklist;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveWorklistRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('worksheets.manage') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:128'],
            'subtitle' => ['nullable', 'string', 'max:128'],
            'footer_text' => ['nullable', 'string', 'max:128'],
            'no_columns' => ['required', Rule::in([1, 2])],
            'orientation' => ['required', Rule::in(['P', 'L'])],
            'analyses' => ['nullable', 'array'],
            'analyses.*' => ['integer', Rule::exists('assays', 'id')],
            'columns' => ['nullable', 'array', 'max:25'],
            'columns.*.name' => ['nullable', 'string', 'max:128'],
            'columns.*.group' => ['nullable', 'string', 'max:128'],
            'columns.*.tick_on' => ['nullable', 'array'],
            'columns.*.tick_on.*' => ['integer', Rule::exists('assays', 'id')],
            'extra_rows' => ['nullable', 'string', 'max:5000', function (string $attribute, mixed $value, \Closure $fail): void {
                if (count(array_filter(array_map('trim', preg_split('/\R/', (string) $value)))) >= Worklist::ROWS_PER_COLUMN) {
                    $fail('Maximaal '.(Worklist::ROWS_PER_COLUMN - 1).' specifieke rijen.');
                }
            }],
            'extra_pages' => ['nullable', 'string', 'max:10000000'],
        ];
    }
}
