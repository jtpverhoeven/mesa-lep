<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\NormalizesAssayConfirmationPayload;
use App\Models\Assay;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAssayRequest extends FormRequest
{
    use NormalizesAssayConfirmationPayload;

    public function authorize(): bool
    {
        return $this->user()->can('create', Assay::class);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:128', Rule::unique('assays')->where('active', 1)],
            'type_base' => ['required', 'integer', Rule::exists('assaytypes', 'id')->where('active', 1)],
            'type' => ['required', Rule::in([1, 2, 3, 4])],
            'media' => ['nullable', 'array', function ($attribute, $value, $fail) {
                if (is_array($value) && strlen(json_encode($value)) > 128) {
                    $fail('De selectie media is te groot (maximaal 128 tekens).');
                }
            }],
            'media.*' => ['required', 'integer', 'distinct', Rule::exists('media', 'id')->where('active', 1)],
            'matrices' => ['nullable', 'array'],
            'matrices.*' => ['required', 'integer', 'distinct', Rule::exists('matrix', 'id')->where('active', 1)],
            'meta_assays' => ['required_if:type,4', 'array'],
            'meta_assays.*' => ['required', 'integer', 'distinct', Rule::exists('assays', 'id')->where('active', 1)],
            'dillution' => ['required', 'boolean'],
            'replicates' => ['required', 'boolean'],
            'min_count' => ['required', 'integer', 'min:0', 'max:2147483647'],
            'max_count' => ['required', 'integer', 'gte:min_count', 'max:2147483647'],
            'duration' => ['nullable', 'string', 'max:5', 'regex:/^\d+(\.\d+)?$/'],
            'start_from' => ['required', Rule::in(['r', 'i'])],
            'confirmation' => ['required', 'boolean'],
            'confirmation_type' => ['required', Rule::in([0, 1])],
            'confirmation_init' => ['required', Rule::in([0, 1, 2])],
            'confirmation_depth' => ['nullable', 'integer', 'min:0'],
            'confirmation_script' => ['required', 'array'],
            'confirmation_script.*.mediaId' => ['required', 'integer', Rule::exists('media', 'id')],
            'confirmation_script.*.chainId' => ['required', 'integer', 'min:1'],
            'confirmation_script.*.disposition' => ['required', Rule::in(['+', '-', '?'])],
            'confirmation_support' => ['nullable', 'array'],
            'confirmation_support.*.mediaId' => ['required', 'integer', Rule::exists('media', 'id')],
            'confirmation_support.*.chainId' => ['required', 'integer', 'min:1'],
            'show_conf_table' => ['nullable', 'integer', Rule::exists('confirmationtables', 'id')],
            'hide_report' => ['required', 'boolean'],
            'uses_indicator' => ['required', 'boolean'],
            'uses_trip_indicator' => ['required', 'boolean'],
            'billable' => ['required', 'boolean'],
            'article_code' => ['nullable', 'string', 'max:255'],
            'script' => ['nullable', 'string', 'max:60000'],
            'custom_fields' => ['nullable', 'array'],
            'custom_fields.*' => ['nullable', 'string', 'max:1000'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->normalizeAssayConfirmationPayload();
    }
}
