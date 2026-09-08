<?php

namespace App\Actions\SampleProcedures;

use App\Models\SampleProcedure;

class DeactivateSampleProcedure
{
    public function handle(SampleProcedure $procedure): SampleProcedure
    {
        $procedure->update(['active' => 0]);

        return $procedure->fresh();
    }
}