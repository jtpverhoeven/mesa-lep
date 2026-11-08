<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreSampleFileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->canAny(['samples.view', 'projects.view']) ?? false;
    }

    /**
     * @return array<string, array<mixed>>
     */
    public function rules(): array
    {
        return [
            'file' => ['required', 'file'],
            'photo_mode' => ['sometimes', 'boolean'],
            'instant_visible' => ['sometimes', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'photo_mode' => $this->normalizeBoolean($this->input('photo_mode', $this->input('photoMode', false))),
            'instant_visible' => $this->normalizeBoolean($this->input('instant_visible', $this->input('instantVis', false))),
        ]);
    }

    private function normalizeBoolean(mixed $value): mixed
    {
        return match ($value) {
            'true' => true,
            'false' => false,
            default => $value,
        };
    }
}
