<?php

namespace App\Actions\SampleProcedures;

use App\Models\SampleProcedure;

class ToggleSampleProcedureVisibility
{
    public function handle(SampleProcedure $procedure): SampleProcedure
    {
        $procedure->update(['active' => (int) ! $procedure->active]);

        return $procedure->fresh();
    }
}