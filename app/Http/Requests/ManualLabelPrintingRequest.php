<?php

namespace App\Http\Requests;

use App\Actions\Printing\SelectManualPrintSamples;
use App\Models\AssayType;
use App\Models\LabelDesign;
use App\Models\LabelEvent;
use App\Models\Printer;
use App\Models\PrintProfile;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class ManualLabelPrintingRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('scan-to-print.use') ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<mixed>>
     */
    public function rules(SelectManualPrintSamples $selection): array
    {
        if ($this->filled('confirmation_token')) {
            return ['confirmation_token' => ['required', 'uuid']];
        }

        return [
            'mode' => ['required', Rule::in(['scan', 'date_range', 'sample_range'])],
            'barcode' => ['exclude_unless:mode,scan', 'required', 'string', 'max:64', 'regex:/^[^.]+(?:\.\d+){0,2}$/'],
            'bar_range_start' => ['exclude_unless:mode,sample_range', 'required', 'string', 'max:64'],
            'bar_range_stop' => ['exclude_unless:mode,sample_range', 'required', 'string', 'max:64'],
            'range_type_select' => ['exclude_unless:mode,date_range', 'required', Rule::in(array_column($selection->dateTypes(), 'id'))],
            'range_start' => ['exclude_unless:mode,date_range', 'required', 'date_format:Y-m-d'],
            'range_stop' => ['exclude_unless:mode,date_range', 'required', 'date_format:Y-m-d', 'after_or_equal:range_start'],
            'date_offset' => ['required', 'date_format:Y-m-d'],
            'print_normal_samples' => ['sometimes', 'boolean'],
            'print_rodac_samples' => ['sometimes', 'boolean'],
            'duplicate_label' => ['sometimes', 'boolean'],
            'print_sample' => ['sometimes', 'boolean'],
            'print_analysis' => ['sometimes', 'boolean'],
            'also_print' => ['sometimes', 'boolean'],
            'print_sample_amount' => [Rule::excludeIf(! $this->boolean('print_sample')), 'required', 'integer', 'between:1,'.LabelEvent::MAX_COPIES],
            'also_print_amount' => [Rule::excludeIf(! $this->boolean('also_print')), 'required', 'integer', 'between:1,'.LabelEvent::MAX_COPIES],
            'also_print_selection' => [Rule::excludeIf(! $this->boolean('also_print')), 'required', 'integer', Rule::exists(LabelDesign::class, 'id')],
            'printer' => ['nullable', 'integer', 'min:0', Rule::when($this->integer('printer') !== 0, Rule::exists(Printer::class, 'id'))],
            'sample_label_select' => ['sometimes', Rule::in(array_merge(['default'], LabelDesign::query()->pluck('id')->all()))],
            'assay_label_select' => ['sometimes', Rule::in(array_merge(['default'], LabelDesign::query()->pluck('id')->all()))],
            'assay_type_select' => ['nullable', 'integer', 'min:0', Rule::when($this->integer('assay_type_select') !== 0, Rule::exists(AssayType::class, 'id'))],
            'profile_ids' => ['sometimes', 'array'],
            'profile_ids.*' => ['integer', 'distinct', Rule::exists(PrintProfile::class, 'id')],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty() || $this->filled('confirmation_token')) {
                return;
            }

            if ($this->boolean('duplicate_label') && ($this->input('mode') !== 'scan' || $this->boolean('print_sample') || $this->boolean('print_analysis') || $this->input('profile_ids', []) !== [])) {
                $validator->errors()->add('duplicate_label', 'Dupliceren kan alleen met een scan, zonder monsterlabels of printprofielen.');
            }

            if (! $this->boolean('duplicate_label') && ! $this->boolean('print_sample') && ! $this->boolean('print_analysis') && ! $this->boolean('also_print') && $this->input('profile_ids', []) === []) {
                $validator->errors()->add('options', 'Selecteer een printprofiel of een label om te printen.');
            }

            if ($this->input('mode') === 'sample_range' && ! $this->boolean('print_normal_samples') && ! $this->boolean('print_rodac_samples')) {
                $validator->errors()->add('print_normal_samples', 'Selecteer minstens een monstertype.');
            }
        }];
    }
}
