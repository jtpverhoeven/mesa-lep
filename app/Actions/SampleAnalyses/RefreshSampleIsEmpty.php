<?php

namespace App\Actions\SampleAnalyses;

use App\Models\Sample;

class RefreshSampleIsEmpty
{
    public function handle(Sample $sample): bool
    {
        $isEmpty = $sample->analyses()->doesntExist();

        $sample->update(['isEmpty' => (int) $isEmpty]);

        return $isEmpty;
    }
}
