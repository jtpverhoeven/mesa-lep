<?php

namespace App\Actions\Results;

use App\Models\SampleAnalysis;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GetMetaAnalysisResults
{
    /** @return array<int, array<string, list<array<string, mixed>>>> */
    public function handle(SampleAnalysis $analysis): array
    {
        $analyses = SampleAnalysis::query()
            ->where('sample', $analysis->sample)
            ->with(['results' => fn (HasMany $results) => $results->reorder()->orderBy('follow_no')->orderBy('id')])
            ->orderBy('id')
            ->get();
        $groups = [];

        foreach ($analyses as $related) {
            if ($related->results->isEmpty()) {
                continue;
            }

            $originalAssay = (int) $related->original_assay_base;
            $groups[$originalAssay] = [];

            foreach ($related->results as $result) {
                $groups[$originalAssay][(string) $result->df][] = $result->data;
            }
        }

        return $groups;
    }
}
