<?php

namespace App\Actions\SampleProcedures;

use App\Models\SampleProcedureField;

class CreateSampleProcedureField
{
    public function handle(array $data): SampleProcedureField
    {
        return SampleProcedureField::create($data);
    }
}