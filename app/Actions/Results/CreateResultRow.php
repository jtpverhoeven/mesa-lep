<?php

namespace App\Actions\Results;

use App\Models\Result;
use App\Models\SampleAnalysis;

class CreateResultRow
{
    public function handle(SampleAnalysis $analysis, string $dilution, int $replicate, int $followNumber, object $data): Result
    {
        return Result::create([
            'sample' => $analysis->sample,
            'sa_id' => $analysis->id,
            'follow_no' => $followNumber,
            'profile' => $analysis->profile,
            'assay' => $analysis->assay,
            'assay_base' => $analysis->assay_base,
            'roaming_id' => $analysis->roaming_id ?? 0,
            'df' => $dilution,
            'rep' => $replicate,
            'data' => $data,
        ]);
    }
}
