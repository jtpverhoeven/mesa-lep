<?php

namespace App\Calculations;

use App\Calculations\Concerns\BuildsKveResult;
use App\Calculations\Contracts\HandlesIncompleteResults;
use App\Calculations\Contracts\ResultCalculation;

/**
 * Port of the legacy `mpn` script: reports the entered most probable number of the undiluted entry.
 */
class MpnCalculation implements HandlesIncompleteResults, ResultCalculation
{
    use BuildsKveResult;

    public function calculate(ResultCalculationContext $context): array
    {
        $value = $this->undilutedData($context)['kve'] ?? null;

        if (! is_numeric($value)) {
            return $this->readyResult($context, ['kve' => $value], null);
        }

        return $this->readyResult($context, ['kve' => number_format((float) $value, 0, '.', '.')], (float) $value);
    }

    /**
     * A negative result does not need an MPN value.
     */
    public function allowsEmptyResultField(array $data, string $field): bool
    {
        return $field === 'MPNwaarde' && in_array((string) ($data['kve'] ?? ''), ['-', '0']);
    }
}
