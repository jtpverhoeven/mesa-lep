<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreSampleBufferRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->input('tab') === 'staged_tht'
            ? $this->user()->can('shelf-life-studies.view')
            : $this->user()->can('portal.access');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer', Rule::exists('samplebuffers', 'id')],
            'action' => ['required', Rule::in(['commit', 'delete', 'sampling_date', 'sampling_method', 'move_to_tht', 'tht_date', 'storage', 'receive', 'metadata'])],
            'tab' => ['required', Rule::in(['normal', 'tht', 'legionella', 'rodac', 'staged_tht'])],
            'date' => ['required_if:action,sampling_date,move_to_tht,tht_date', 'nullable', 'date'],
            'sampling_method' => ['required_if:action,sampling_method', 'nullable', 'integer', Rule::exists('sampleprocedures', 'id')],
            'storage' => ['required_if:action,move_to_tht,storage', 'nullable', Rule::in(['0', '1', '2', '3', '4'])],
            'receive_date' => ['required_if:action,receive', 'nullable', 'date'],
            'receive_time' => ['required_if:action,receive', 'nullable', 'date_format:H:i'],
            'meta' => ['nullable', 'array'],
        ];
    }

    /** @return array<callable> */
    public function after(): array
    {
        return [function (Validator $validator): void {
            $allowedActions = [
                'normal' => ['commit', 'delete', 'sampling_date', 'sampling_method', 'move_to_tht', 'metadata'],
                'tht' => ['commit', 'delete', 'sampling_date', 'sampling_method', 'tht_date', 'metadata'],
                'legionella' => ['commit', 'delete', 'sampling_date', 'sampling_method', 'metadata'],
                'rodac' => ['commit', 'delete', 'sampling_date', 'sampling_method', 'metadata'],
                'staged_tht' => ['commit', 'delete', 'tht_date', 'storage', 'receive', 'metadata'],
            ];
            $tab = $this->string('tab')->toString();
            $action = $this->string('action')->toString();

            if (! in_array($action, $allowedActions[$tab] ?? [], true)) {
                $validator->errors()->add('action', 'Deze actie is niet beschikbaar voor de gekozen lijst.');
            }

            if ($action === 'commit' && $tab !== 'staged_tht') {
                if (! $this->filled('receive_date')) {
                    $validator->errors()->add('receive_date', 'De ontvangstdatum is verplicht.');
                }
                if (! $this->filled('receive_time')) {
                    $validator->errors()->add('receive_time', 'De ontvangsttijd is verplicht.');
                }
            }
        }];
    }
}
