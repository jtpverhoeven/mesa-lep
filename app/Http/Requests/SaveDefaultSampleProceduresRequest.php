<?php

namespace App\Http\Requests;

use App\Models\SampleProcedure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveDefaultSampleProceduresRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('sampling-procedures.manage');
    }

    public function rules(): array
    {
        $procedureIds = SampleProcedure::where('active', 1)->pluck('id')->prepend(0)->all();

        return [
            'default_proc' => ['required', 'integer', Rule::in($procedureIds)],
            'default_leg_proc' => ['required', 'integer', Rule::in($procedureIds)],
            'default_rodac_proc' => ['required', 'integer', Rule::in($procedureIds)],
        ];
    }
}