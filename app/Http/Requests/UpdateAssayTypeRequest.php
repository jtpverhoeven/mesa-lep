<?php

namespace App\Http\Requests;

use App\Models\Assay;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAssayTypeRequest extends FormRequest
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
                'max:128',
                Rule::unique('assaytypes')->where('active', 1)->ignore($assayTypeId),
            ],
            'description' => ['nullable', 'string', 'max:10000'],
        ];
    }
}