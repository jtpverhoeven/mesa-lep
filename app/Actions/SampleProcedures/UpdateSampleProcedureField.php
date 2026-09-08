<?php

namespace App\Actions\SampleProcedures;

use App\Models\SampleProcedureField;

class UpdateSampleProcedureField
{
    public function handle(SampleProcedureField $field, array $data): SampleProcedureField
    {
        $field->update($data);

        return $field->fresh();
    }
}