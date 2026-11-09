<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SendProjectFilesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return [
            'receivers' => ['nullable', 'array', 'max:200'],
            'receivers.*' => ['string', 'max:64'],
            'extraReceiver' => ['nullable', 'string', 'max:2000'],
            'cc' => ['nullable', 'string', 'max:2000'],
            'bcc' => ['nullable', 'string', 'max:2000'],
            'messageFiles' => ['nullable', 'string', 'max:65535'],
            'includeSampleFiles' => ['nullable', 'array', 'max:500'],
            'includeSampleFiles.*' => ['string', 'max:255'],
        ];
    }

    /** @return array{receivers: list<string>, extra: string, cc: string, bcc: string, message: string} */
    public function mailInput(): array
    {
        return [
            'receivers' => array_values($this->input('receivers', [])),
            'extra' => (string) $this->input('extraReceiver', ''),
            'cc' => (string) $this->input('cc', ''),
            'bcc' => (string) $this->input('bcc', ''),
            'message' => (string) $this->input('messageFiles', ''),
        ];
    }

    /** @return list<string> */
    public function hashes(): array
    {
        return array_values($this->input('includeSampleFiles', []));
    }
}
