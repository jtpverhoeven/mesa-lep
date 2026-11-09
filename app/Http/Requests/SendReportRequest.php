<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SendReportRequest extends FormRequest
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
            'message' => ['nullable', 'string', 'max:65535'],
            'messageAtt' => ['nullable', 'string', 'max:65535'],
            'messageFiles' => ['nullable', 'string', 'max:65535'],
            'exportAll' => ['nullable', 'boolean'],
            'selectedReports' => ['nullable', 'array', 'max:500'],
            'selectedReports.*' => ['integer'],
            'includeSampleFiles' => ['nullable', 'array', 'max:500'],
            'includeSampleFiles.*' => ['string', 'max:255'],
            'includeOrphanedSampleFiles' => ['nullable', 'array', 'max:500'],
            'includeOrphanedSampleFiles.*' => ['string', 'max:255'],
        ];
    }

    /**
     * @return array{receivers: list<string>, extra: string, cc: string, bcc: string, message: string, messageAtt: string, messageFiles: string, exportAll: bool, selectedReports: list<int>, includeSampleFiles: list<string>, includeOrphanedSampleFiles: list<string>}
     */
    public function mailInput(): array
    {
        return [
            'receivers' => array_values($this->input('receivers', [])),
            'extra' => (string) $this->input('extraReceiver', ''),
            'cc' => (string) $this->input('cc', ''),
            'bcc' => (string) $this->input('bcc', ''),
            'message' => (string) $this->input('message', ''),
            'messageAtt' => (string) $this->input('messageAtt', ''),
            'messageFiles' => (string) $this->input('messageFiles', ''),
            'exportAll' => $this->boolean('exportAll'),
            'selectedReports' => array_map('intval', $this->input('selectedReports', [])),
            'includeSampleFiles' => array_values($this->input('includeSampleFiles', [])),
            'includeOrphanedSampleFiles' => array_values($this->input('includeOrphanedSampleFiles', [])),
        ];
    }
}
