<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\NormalizesAssayConfirmationPayload;
use App\Models\Assay;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAssayRequest extends FormRequest
{
    use NormalizesAssayConfirmationPayload;

    public function authorize(): bool
    {
        $assay = $this->route('assay');

        return $assay instanceof Assay && $this->user()->can('update', $assay);
    }

    public function rules(): array
    {
        $assay = $this->route('assay');
        $assayId = $assay instanceof Assay ? $assay->getKey() : $assay;
        $attachedMediaIds = $assay instanceof Assay
            ? collect(json_decode((string) $assay->media_id, true) ?: explode(',', (string) $assay->media_id))
                ->filter(static fn ($id): bool => is_numeric($id))
                ->map(static fn ($id): int => (int) $id)
                ->unique()
                ->values()
                ->all()
            : [];

        return [
            'name' => [
                'required',
                'string',
                'max:128',
                Rule::unique('assays')->where('active', 1)->ignore($assayId),
            ],
            'type_base' => ['required', 'integer', Rule::exists('assaytypes', 'id')->where('active', 1)],
            'type' => ['required', Rule::in([1, 2, 3, 4])],
            'media' => ['nullable', 'array', function ($attribute, $value, $fail) {
                if (is_array($value) && strlen(json_encode($value)) > 128) {
                    $fail('De selectie media is te groot (maximaal 128 tekens).');
                }
            }],
            'media.*' => [
                'required',
                'integer',
                'distinct',
                Rule::exists('media', 'id')->where(function ($query) use ($attachedMediaIds) {
                    $query->where('active', 1);

                    if ($attachedMediaIds !== []) {
                        $query->orWhereIn('id', $attachedMediaIds);
                    }
                }),
            ],
            'matrices' => ['nullable', 'array'],
            'matrices.*' => ['required', 'integer', 'distinct', Rule::exists('matrix', 'id')->where('active', 1)],
            'meta_assays' => ['required_if:type,4', 'nullable', 'array'],
            'meta_assays.*' => ['required', 'integer', 'distinct', Rule::exists('assays', 'id')->where('active', 1)],
            'dillution' => ['sometimes', 'boolean'],
            'replicates' => ['sometimes', 'boolean'],
            'min_count' => ['sometimes', 'required', 'integer', 'min:0', 'max:2147483647'],
            'max_count' => ['sometimes', 'required', 'integer', 'gte:min_count', 'max:2147483647'],
            'duration' => ['sometimes', 'nullable', 'string', 'max:5', 'regex:/^\d+(\.\d+)?$/'],
            'start_anchor' => ['required', Rule::in(['r', 'i', 'p', 's'])],
            'start_field_name' => ['nullable', 'required_if:start_anchor,p', 'required_if:start_anchor,s', 'string', 'max:126'],
            'confirmation' => ['sometimes', 'boolean'],
            'confirmation_type' => ['sometimes', Rule::in([0, 1])],
            'confirmation_init' => ['sometimes', Rule::in([0, 1, 2])],
            'confirmation_depth' => ['sometimes', 'nullable', 'integer', 'min:0'],
            'confirmation_script' => ['sometimes', 'array'],
            'confirmation_script.*.mediaId' => ['required', 'integer', Rule::exists('media', 'id')],
            'confirmation_script.*.chainId' => ['required', 'integer', 'min:1'],
            'confirmation_script.*.disposition' => ['required', Rule::in(['+', '-', '?'])],
            'confirmation_support' => ['sometimes', 'nullable', 'array'],
            'confirmation_support.*.mediaId' => ['required', 'integer', Rule::exists('media', 'id')],
            'confirmation_support.*.chainId' => ['required', 'integer', 'min:1'],
            'show_conf_table' => ['sometimes', 'nullable', 'integer', Rule::exists('confirmationtables', 'id')],
            'hide_report' => ['sometimes', 'boolean'],
            'uses_indicator' => ['sometimes', 'boolean'],
            'uses_trip_indicator' => ['sometimes', 'boolean'],
            'billable' => ['sometimes', 'nullable', 'boolean'],
            'article_code' => ['sometimes', 'nullable', 'string'],
            'script' => ['sometimes', 'nullable', 'string', 'max:60000'],
            'custom_fields' => ['nullable', 'array'],
            'custom_fields.*' => ['nullable', 'string', 'max:1000'],
            'force_changes' => ['sometimes', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->normalizeAssayConfirmationPayload();
    }
}
