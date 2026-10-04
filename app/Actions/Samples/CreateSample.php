<?php

namespace App\Actions\Samples;

use App\Models\Sample;
use App\Models\SampleField;

class CreateSample
{
    public function __construct(private CreateRegisteredSample $create) {}

    public function handle(array $data): Sample
    {
        return $this->create->handle([
            ...$data,
            'custom_fields' => $this->customFields($data['custom_fields'] ?? []),
            'sample_type' => 'S',
            'leg_type' => '-',
        ]);
    }

    private function customFields(array $values): array
    {
        return SampleField::query()
            ->orderBy('position')
            ->get(['name', 'std_value'])
            ->mapWithKeys(fn (SampleField $field) => [$field->name => $values[$field->name] ?? $field->std_value])
            ->all();
    }
}
