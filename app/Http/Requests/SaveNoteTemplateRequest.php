<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SaveNoteTemplateRequest extends FormRequest
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
        return [
            'name' => ['required', 'string', 'max:128'],
            'content' => ['required', 'string', 'max:65535'],
        ];
    }
}
