<?php

namespace App\Calculations;

use App\Calculations\Concerns\BuildsKveResult;
use App\Calculations\Contracts\HandlesIncompleteResults;
use App\Calculations\Contracts\ResultCalculation;

/**
 * Port of the legacy `ph` script: averages the pH measurements entered on the undiluted plate.
 */
class PhCalculation implements HandlesIncompleteResults, ResultCalculation
{
    use BuildsKveResult;

    private const MAXIMUM_PH = 14;

    private const MAXIMUM_SPREAD = 0.5;

    public function calculate(ResultCalculationContext $context): array
    {
        $measurements = [];

        // Legacy casts every field, so text such as `-` is measured as 0.
        foreach ($this->undilutedData($context) ?? [] as $value) {
            $measurement = (float) str_replace(',', '.', (string) $value);

            if ($measurement > self::MAXIMUM_PH) {
                return $this->failedResult($context);
            }

            $measurements[] = $measurement;
        }

        if ($measurements === []) {
            return $this->failedResult($context, result: 'Geen resultaten gevonden');
        }

        if (max($measurements) - min($measurements) > self::MAXIMUM_SPREAD) {
            return $this->failedResult($context, result: 'Resultaten lagen meer dan 0.5 af van elkaar');
        }

        $average = round(array_sum($measurements) / count($measurements), 2);

        return $this->readyResult($context, ['kve' => number_format($average, 2, ',', '')], $average);
    }
}
