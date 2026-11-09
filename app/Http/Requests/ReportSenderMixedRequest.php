<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ReportSenderMixedRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return [
            'selected_files' => ['nullable', 'array', 'max:500'],
            'selected_files.*' => ['string', 'max:255'],
        ];
    }
}
