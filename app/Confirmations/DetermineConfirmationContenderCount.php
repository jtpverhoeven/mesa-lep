<?php

namespace App\Confirmations;

use App\Actions\Results\GetMetaAnalysisResults;
use App\Models\Result;
use App\Models\SampleAnalysis;

class DetermineConfirmationContenderCount
{
    public function __construct(private GetMetaAnalysisResults $metaResults) {}

    /**
     * @return array{applicable: bool, count: int}
     */
    public function handle(SampleAnalysis $analysis, string $df, int $rep, int $depth): array
    {
        $analysis->loadMissing('results');

        if ($df === 'global') {
            $count = 0;
            $applicable = false;
            $values = $analysis->results->map(fn (Result $result): mixed => $result->data['kve'] ?? null)->all();

            if ((int) $analysis->assayRecord?->type === 4) {
                foreach ($this->metaResults->handle($analysis) as $originalAssay => $dilutions) {
                    if ((int) $originalAssay === (int) $analysis->assayRecord->original_id) {
                        continue;
                    }

                    foreach ($dilutions as $plates) {
                        foreach ($plates as $plate) {
                            $values[] = $plate['kve'] ?? null;
                        }
                    }
                }
            }

            foreach ($values as $value) {
                [$resultCount, $resultApplicable] = $this->valueCount($value, $depth);
                $count += $resultCount;
                $applicable = $applicable || $resultApplicable;
            }

            return [
                'applicable' => $applicable,
                'count' => min(max(0, $depth), $count),
            ];
        }

        $result = $analysis->results->first(
            fn (Result $result): bool => (string) $result->df === $df && (int) $result->rep === $rep,
        );

        if ($result === null) {
            return ['applicable' => false, 'count' => 0];
        }

        [$count, $applicable] = $this->valueCount($result->data['kve'] ?? null, $depth);

        return [
            'applicable' => $applicable,
            'count' => min(max(0, $depth), $count),
        ];
    }

    /** @return array{int, bool} */
    private function valueCount(mixed $value, int $depth): array
    {
        if ($value === '0' || $value === '>' || (is_numeric($value) && (float) $value === 0.0)) {
            return [0, false];
        }

        if ($value === '+') {
            return [max(0, $depth), true];
        }

        if (is_numeric($value) && (float) $value > 0) {
            return [(int) ceil((float) $value), true];
        }

        return [0, false];
    }
}
