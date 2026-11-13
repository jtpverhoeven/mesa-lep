<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PrintWorklistsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('lists.view') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'worklist' => ['required', Rule::when($this->input('worklist') !== 'all', Rule::exists('worklists', 'id'))],
            'date' => ['required', 'date_format:Y-m-d'],
        ];
    }
}
