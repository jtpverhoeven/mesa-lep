<?php

namespace App\Http\Requests;

use App\Actions\SampleBuffers\ReadImportCsv;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PreviewSampleBufferImportRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('clients.import');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'client' => ['required', 'integer', Rule::exists('clients', 'id')->where('active', 1)],
            'file' => ['required', 'file', 'extensions:csv,txt', 'max:2048'],
            'separator' => ['nullable', Rule::in(['auto', ...array_keys(ReadImportCsv::SEPARATORS)])],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'client.required' => 'Kies eerst een klant.',
            'client.exists' => 'Deze klant bestaat niet of is niet actief.',
            'file.required' => 'Kies een CSV-bestand.',
            'file.extensions' => 'Alleen CSV-bestanden worden ondersteund. Sla het werkblad in Excel op als CSV.',
            'file.max' => 'Het bestand is te groot (maximaal 2 MB).',
            'file.uploaded' => 'Het bestand kon niet worden geüpload (maximaal 2 MB).',
        ];
    }
}
