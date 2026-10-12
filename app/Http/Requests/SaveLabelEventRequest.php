<?php

namespace App\Http\Requests;

use App\Models\Assay;
use App\Models\AssayType;
use App\Models\Client;
use App\Models\LabelDesign;
use App\Models\LabelEvent;
use App\Models\Printer;
use App\Models\SampleField;
use App\Models\SampleProcedure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class SaveLabelEventRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('labels.print-settings') ?? false;
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        $group = is_scalar($this->input('event_group')) ? (int) $this->input('event_group') : 0;
        $conditional = $this->input('event') === 'L';
        $type = $this->input('event_data.type');
        $valueRules = match ($type) {
            'sField' => ['present', 'nullable', 'string', 'max:65535'],
            'sClient' => ['required', 'integer', Rule::exists(Client::class, 'id')],
            'mMethod' => ['required', 'integer', Rule::exists(SampleProcedure::class, 'id')],
            'mType' => ['required', 'string', Rule::in(['S', 'L', 'R'])],
            'assayTypeIs' => ['required', 'integer', Rule::exists(AssayType::class, 'id')],
            'assayIs' => ['required', 'string', 'max:65535', 'regex:/\A[1-9]\d*(?:,[1-9]\d*)*\z/'],
            default => ['required', 'string'],
        };

        return [
            'event' => ['required', 'string', Rule::in($group === 3 ? ['P'] : ['P', 'L'])],
            'event_group' => ['required', 'integer', Rule::in(array_keys(LabelEvent::GROUPS))],
            'print_label' => ['required', 'integer', Rule::exists(LabelDesign::class, 'id')],
            'print_to' => ['required', 'integer', Rule::exists(Printer::class, 'id')],
            'copies' => ['required', 'integer', 'min:1', 'max:'.LabelEvent::MAX_COPIES],
            'event_data' => ['exclude_unless:event,L', 'required', 'array:type,inspect,inspect_value,negative'],
            'event_data.type' => ['exclude_unless:event,L', 'required', 'string', Rule::in(array_keys(LabelEvent::CONDITIONS[$group] ?? []))],
            'event_data.inspect' => [Rule::excludeIf(! $conditional || $type !== 'sField'), 'required', 'string', Rule::exists(SampleField::class, 'name')->whereNull('deleted_at')],
            'event_data.inspect_value' => [Rule::excludeIf(! $conditional), ...$valueRules],
            'event_data.negative' => ['exclude_unless:event,L', 'required', Rule::in(['0', '1'])],
        ];
    }

    /** @return array<int, callable> */
    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty() || $this->input('event') !== 'L' || $this->input('event_data.type') !== 'assayIs') {
                return;
            }

            $ids = array_unique(explode(',', $this->input('event_data.inspect_value')));

            if (Assay::query()->whereKey($ids)->count() !== count($ids)) {
                $validator->errors()->add('event_data.inspect_value', 'Een of meer geselecteerde analyses bestaan niet.');
            }
        }];
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->input('event_data'))) {
            $condition = json_decode($this->input('event_data'), true);

            if (is_array($condition)) {
                $this->merge(['event_data' => $condition]);
            }
        }
    }

    public function messages(): array
    {
        return [
            'event.in' => 'Dit regeltype is niet beschikbaar voor dit printmoment.',
            'event_data.type.in' => 'Kies een voorwaarde die bij dit printmoment hoort.',
        ];
    }

    public function attributes(): array
    {
        return [
            'event' => 'regeltype', 'event_group' => 'printmoment', 'print_label' => 'labelontwerp',
            'print_to' => 'printer', 'copies' => 'aantal exemplaren', 'event_data' => 'voorwaarde',
            'event_data.type' => 'voorwaarde', 'event_data.inspect' => 'monsterveld',
            'event_data.inspect_value' => 'vergelijkingswaarde', 'event_data.negative' => 'omgekeerde voorwaarde',
        ];
    }
}
