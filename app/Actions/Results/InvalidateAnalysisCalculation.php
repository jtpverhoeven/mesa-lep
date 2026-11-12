<?php

namespace App\Actions\Results;

use App\Jobs\CalculateAnalysisResultJob;
use App\Models\SampleAnalysis;

class InvalidateAnalysisCalculation
{
    public function __construct(private GetMetaAnalysisParent $metaParent) {}

    /** Drop the stored result of the analysis (and its meta parent) and queue a fresh calculation. */
    public function handle(SampleAnalysis $analysis): void
    {
        $analysis->update(['is_ready' => false, 'storedResult' => null]);
        CalculateAnalysisResultJob::dispatch($analysis->id)->afterCommit();
        $parent = $this->metaParent->handle($analysis);

        if ($parent !== null) {
            $parent->update(['is_ready' => false, 'storedResult' => null]);
            CalculateAnalysisResultJob::dispatch($parent->id)->afterCommit();
        }
    }
}
