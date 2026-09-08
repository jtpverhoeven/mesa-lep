<?php

namespace App\Actions\SampleProcedures;

use App\Models\SampleProcedureField;

class DeleteSampleProcedureField
{
    public function handle(SampleProcedureField $field): bool
    {
        return $field->delete();
    }
}