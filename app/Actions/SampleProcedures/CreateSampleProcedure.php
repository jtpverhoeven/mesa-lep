<?php

namespace App\Actions\SampleProcedures;

use App\Models\SampleProcedure;
use App\Models\SampleProcedureField;

class CreateSampleProcedure
{
    public function handle(array $data): SampleProcedure
    {
        return SampleProcedure::create([
            'name' => $data['name'],
            'hide' => $data['hide'],
            'fields' => $this->fields($data),
        ]);
    }

    private function fields(array $data): string
    {
        $values = SampleProcedureField::orderBy('position')->get()->mapWithKeys(
            fn (SampleProcedureField $field) => [$field->name => $data['fields'][$field->id] ?? '']
        );

        return json_encode((object) $values->all(), JSON_THROW_ON_ERROR);
    }
}