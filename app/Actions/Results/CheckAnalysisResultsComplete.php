<?php

namespace App\Actions\Results;

use App\Calculations\Contracts\DeterminesResultCompleteness;
use App\Calculations\Contracts\HandlesIncompleteResults;
use App\Calculations\Contracts\ResultCalculation;
use App\Calculations\ResultCalculationContext;
use App\Support\DilutionCurve;

class CheckAnalysisResultsComplete
{
    public function handle(ResultCalculationContext $context, ?ResultCalculation $calculator = null): bool
    {
        if ($calculator instanceof DeterminesResultCompleteness) {
            return $calculator->resultsAreComplete($context);
        }

        // The stored rows are authoritative: dilutions can be changed per sample after the rows were created.
        $seenRows = [];

        foreach ($context->analysis->results as $result) {
            $row = DilutionCurve::format((float) $result->df).':'.(int) $result->rep;

            if (isset($seenRows[$row]) || ! is_array($result->data)) {
                return false;
            }

            $seenRows[$row] = true;

            foreach ($result->data as $field => $value) {
                if (($value === '' || $value === null)
                    && ! ($calculator instanceof HandlesIncompleteResults && $calculator->allowsEmptyResultField($result->data, $field))) {
                    return false;
                }
            }
        }

        return $seenRows !== [];
    }
}
