<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePortalAssayRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('settings.advanced') ?? false;
    }

    public function rules(): array
    {
        return array_merge($this->portalAssayRules(), [
            'add_to_all_clients' => ['required', 'boolean'],
        ]);
    }

    /**
     * @return array<string, array<int, string>>
     */
    protected function portalAssayRules(): array
    {
        return [
            'common_name' => ['required', 'string', 'max:1000'],
            'common_name_en' => ['nullable', 'string', 'max:1000'],
            'selectable' => ['required', 'boolean'],
            'alertable' => ['required', 'boolean'],
            'border_reaction' => ['required', 'boolean'],
        ];
    }
}
