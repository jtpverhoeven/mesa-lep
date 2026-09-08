<?php

namespace App\Http\Requests;

use App\Models\Assay;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAssayTypeFieldRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('viewAny', Assay::class);
    }

    public function rules(): array
    {
        $assayType = $this->route('assayType');
        $assayTypeId = $assayType?->getKey();

        return [
            'name' => [
                'required',
                'string',
                'max:32',
                'regex:/^[A-Za-z0-9]+$/',
                Rule::unique('assaytypefields', 'name')->where(
                    fn ($query) => $query->where('test_id', $assayTypeId)
                ),
            ],
            'alias' => ['required', 'string', 'max:64'],
            'type' => ['required', Rule::in(['varchar', 'int', 'float'])],
            'pos' => ['required', 'integer', 'min:0'],
            'endresults_driver' => ['sometimes', 'boolean'],
        ];
    }
}