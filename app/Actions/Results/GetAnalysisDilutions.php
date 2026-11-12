<?php

namespace App\Actions\Results;

use App\Models\SampleAnalysis;
use App\Support\DilutionCurve;

class GetAnalysisDilutions
{
    /**
     * The dilutions currently held by the analysis' result rows, highest concentration first.
     *
     * @return array{analysis_id: int, name: string|null, uses_dilution: bool, uses_replicates: bool, dilutions: list<array{value: string, replicates: int}>}
     */
    public function handle(SampleAnalysis $analysis): array
    {
        $analysis->loadMissing(['assayRecord', 'results']);

        $dilutions = $analysis->results
            ->groupBy(fn ($result): string => DilutionCurve::format((float) $result->df))
            ->map(fn ($rows, string $value): array => ['value' => $value, 'replicates' => (int) $rows->max('rep')])
            ->sortByDesc(fn (array $dilution): float => (float) $dilution['value'])
            ->values()
            ->all();

        return [
            'analysis_id' => $analysis->id,
            'name' => $analysis->assayRecord?->name,
            'uses_dilution' => (bool) $analysis->assayRecord?->dillution,
            'uses_replicates' => (bool) $analysis->assayRecord?->replicates,
            'dilutions' => $dilutions,
        ];
    }
}
