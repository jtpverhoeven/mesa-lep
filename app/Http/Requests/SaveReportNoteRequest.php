<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SaveReportNoteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('projects.notes.create') ?? false;
    }

    /**
     * @return array<string, array<mixed>>
     */
    public function rules(): array
    {
        return ['body' => ['present', 'nullable', 'string', 'max:65535']];
    }
}
