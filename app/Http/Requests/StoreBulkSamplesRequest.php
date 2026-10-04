<?php

namespace App\Http\Requests;

use App\Actions\Samples\BulkSampleRegistrationOptions;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreBulkSamplesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('samples.create');
    }

    public function rules(): array
    {
        $type = $this->route('registrationType');
        $options = app(BulkSampleRegistrationOptions::class);
        $methods = array_column($options->samplingMethods($type), 'id');
        $rules = [
            'client' => ['required', 'integer', Rule::exists('clients', 'id')],
            'project' => ['nullable', 'integer', Rule::exists('projects', 'id')],
            'project_name' => ['nullable', 'string', 'max:128'],
            'project_custom_fields' => ['nullable', 'array'],
            'project_custom_fields.*' => ['nullable', 'string'],
            'sample_note' => ['nullable', 'string'],
            'inoculation_date' => ['nullable', 'date_format:Y-m-d'],
            'inoculation_time' => ['nullable', 'date_format:H:i'],
            'samples' => ['required', 'array', 'min:1'],
            'samples.*' => ['array:follow,profile_id,sampling_method,description,location'],
            'samples.*.follow' => $type === 'legionella' ? ['required', 'integer', 'min:1'] : ['nullable', 'string'],
        ];

        if ($type === 'legionella') {
            $rules['samples.*.profile_id'] = ['required', 'integer', Rule::in(array_column($options->profiles(), 'id'))];
            $rules['samples.*.sampling_method'] = ['required', 'integer', Rule::in($methods)];
        } else {
            $rules['sampling_method'] = ['required', 'integer', Rule::in($methods)];
            $rules['samples.*.description'] = ['nullable', 'string'];
            $rules['samples.*.location'] = ['nullable', 'string'];
        }

        return $rules;
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($this->route('registrationType') === 'legionella' && ! $this->filled('project')
                && trim((string) $this->input('project_custom_fields.project_bemonster_tijd')) === '') {
                $validator->errors()->add('project_custom_fields.project_bemonster_tijd', 'Bemonstertijd niet ingevuld.');
            }
        }];
    }
}
