<?php

namespace App\Actions\SampleProcedures;

use App\Models\SampleProcedure;
use App\Models\SampleProcedureField;

class UpdateSampleProcedure
{
    public function handle(SampleProcedure $procedure, array $data): SampleProcedure
    {
        $procedure->update([
            'name' => $data['name'],
            'hide' => $data['hide'],
            'fields' => $this->fields($procedure, $data),
        ]);

        return $procedure->fresh();
    }

    private function fields(SampleProcedure $procedure, array $data): string
    {
        $existing = json_decode($procedure->fields, true);
        $existing = is_array($existing) ? $existing : [];

        $values = SampleProcedureField::orderBy('position')->get()->mapWithKeys(
            fn (SampleProcedureField $field) => [
                $field->name => $data['fields'][$field->id] ?? $existing[$field->name] ?? '',
            ]
        );

        return json_encode((object) $values->all(), JSON_THROW_ON_ERROR);
    }
}