<?php

namespace App\Http\Requests;

use App\Actions\SampleBuffers\ReadImportCsv;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class CommitSampleBufferImportRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('clients.import');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $date = ['date_format:Y-m-d', 'after:1999-12-31', 'before:2100-01-01'];

        return [
            'client' => ['required', 'integer', Rule::exists('clients', 'id')->where('active', 1)],
            'meta_columns' => ['nullable', 'array', 'max:100'],
            'meta_columns.*' => ['required', 'string', 'max:128', 'distinct'],
            'rows' => ['required', 'array', 'min:1', 'max:'.ReadImportCsv::MAX_ROWS],
            'rows.*.sampling_date' => ['required', ...$date],
            'rows.*.project_name' => ['nullable', 'string', 'max:128'],
            'rows.*.sample_name' => ['nullable', 'string', 'max:2000'],
            'rows.*.sample_details' => ['nullable', 'string', 'max:2000'],
            'rows.*.tht' => ['required', 'boolean'],
            'rows.*.tht_date' => ['nullable', ...$date],
            'rows.*.analyses_selected' => ['nullable', 'string', 'max:2000'],
            'rows.*.misc_directions' => ['nullable', 'string', 'max:2000'],
            'rows.*.product_group' => ['nullable', 'integer', Rule::exists('productgroups', 'portal_id')->where('client_id', $this->input('client'))],
            'rows.*.meta' => ['nullable', 'array'],
            'rows.*.meta.*' => ['nullable', 'string', 'max:2000'],
        ];
    }

    /** @return array<callable> */
    public function after(): array
    {
        return [function (Validator $validator): void {
            $rows = $this->input('rows');

            foreach (is_array($rows) ? $rows : [] as $index => $row) {
                if (! is_array($row)) {
                    continue;
                }

                if (blank($row['project_name'] ?? null) && blank($row['sample_name'] ?? null) && blank($row['sample_details'] ?? null)) {
                    $validator->errors()->add("rows.$index.sample_name", 'Vul minimaal een projectnaam, monsternaam of details in.');
                }

                if (filter_var($row['tht'] ?? false, FILTER_VALIDATE_BOOLEAN) && blank($row['tht_date'] ?? null)) {
                    $validator->errors()->add("rows.$index.tht_date", 'Vul de THT-datum in voor een THT-onderzoek.');
                }
            }
        }];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'client.required' => 'Kies eerst een klant.',
            'client.exists' => 'Deze klant bestaat niet of is niet actief.',
            'rows.required' => 'Er zijn geen rijen om te importeren.',
            'rows.min' => 'Er zijn geen rijen om te importeren.',
            'rows.max' => 'Er kunnen maximaal '.ReadImportCsv::MAX_ROWS.' rijen tegelijk worden geïmporteerd.',
            'rows.*.sampling_date.required' => 'Vul een geldige monsternamedatum in.',
            'rows.*.sampling_date.date_format' => 'Vul een geldige monsternamedatum in.',
            'rows.*.sampling_date.after' => 'De monsternamedatum ligt te ver in het verleden.',
            'rows.*.sampling_date.before' => 'De monsternamedatum ligt te ver in de toekomst.',
            'rows.*.tht_date.date_format' => 'Vul een geldige THT-datum in.',
            'rows.*.tht_date.after' => 'De THT-datum ligt te ver in het verleden.',
            'rows.*.tht_date.before' => 'De THT-datum ligt te ver in de toekomst.',
            'rows.*.product_group.exists' => 'Deze productgroep hoort niet bij de gekozen klant.',
            'rows.*.project_name.max' => 'De projectnaam mag maximaal 128 tekens bevatten.',
            'rows.*.*.max' => 'Deze waarde is te lang.',
        ];
    }
}
