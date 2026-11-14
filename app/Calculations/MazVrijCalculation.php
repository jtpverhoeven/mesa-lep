<?php

namespace App\Calculations;

use App\Calculations\Concerns\BuildsKveResult;
use App\Calculations\Contracts\HandlesIncompleteResults;
use App\Calculations\Contracts\ResultCalculation;

/**
 * Port of the legacy `mazVrij` script: reports the undiluted entry as entered, without any calculation.
 */
class MazVrijCalculation implements HandlesIncompleteResults, ResultCalculation
{
    use BuildsKveResult;

    public function calculate(ResultCalculationContext $context): array
    {
        $value = $this->undilutedData($context)['kve'] ?? null;

        return $this->readyResult($context, ['kve' => $value], is_numeric($value) ? (float) $value : null);
    }
}
