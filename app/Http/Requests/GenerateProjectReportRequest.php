<?php

namespace App\Http\Requests;

use App\Actions\Reports\GetProjectReportOptions;
use App\Models\DocgenTemplate;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class GenerateProjectReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return ($this->user()?->can('reports.generate') ?? false) && $this->user()->can('projects.view');
    }

    public function rules(): array
    {
        $project = $this->route('project');

        return [
            'templateSelect' => ['required', 'string', Rule::in(DocgenTemplate::SUPPORTED_FOLDERS)],
            'PDFtypeSelect' => ['required', Rule::in([0, 1])],
            'reportType' => ['required', Rule::in(['projectReport', 'projectSample'])],
            'reportExportType' => ['required', Rule::in(['create', 'preview'])],
            'noSamples' => ['required', 'integer', 'between:1,5'],
            'showViolation' => ['required', 'boolean'],
            'languageSelect' => ['sometimes', Rule::in(['nl', 'en'])],
            'exportSample' => ['exclude_unless:reportType,projectSample', 'required', 'array', 'min:1'],
            'exportSample.*' => ['integer', 'distinct', Rule::exists('samples', 'id')->where('project', $project->id)],
            'assay_ids' => ['present', 'array'],
            'assay_ids.*' => ['integer', 'distinct'],
            'assay_order' => ['required', 'array'],
            'assay_order.*' => ['integer', 'distinct'],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $ids = app(GetProjectReportOptions::class)->assays($this->route('project'))->modelKeys();
            $selected = array_map('intval', $this->input('assay_ids'));
            $order = array_map('intval', $this->input('assay_order'));

            if (array_diff($selected, $ids) !== []
                || (! $this->user()->can('reports.parameters.update') && count($selected) !== count($ids))) {
                $validator->errors()->add('assay_ids', 'Deze parameterselectie is niet toegestaan voor dit project.');
            }

            if (count($order) !== count($ids) || array_diff($order, $ids) !== []) {
                $validator->errors()->add('assay_order', 'De parametervolgorde moet alle analyses van dit project bevatten.');
            }
        }];
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['assay_ids' => $this->input('assay_ids', [])]);
    }
}
