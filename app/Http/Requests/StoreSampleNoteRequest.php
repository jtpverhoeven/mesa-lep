<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSampleNoteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('samples.notes.update') ?? false;
    }

    /**
     * @return array<string, array<mixed>>
     */
    public function rules(): array
    {
        return [
            'body' => ['required', 'string', 'max:10000'],
            'mentions' => ['sometimes', 'array', 'max:50'],
            'mentions.*' => ['integer', Rule::exists('users', 'id')],
        ];
    }
}
