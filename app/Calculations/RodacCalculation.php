<?php

namespace App\Calculations;

use App\Calculations\Concerns\BuildsKveResult;
use App\Calculations\Contracts\ResultCalculation;

class RodacCalculation implements ResultCalculation
{
    use BuildsKveResult;

    private const REPORT_FIELD = 'kve';

    public function calculate(ResultCalculationContext $context): array
    {
        $value = $this->undilutedData($context)[self::REPORT_FIELD] ?? null;

        if (! is_numeric($value)) {
            return $this->failedResult($context);
        }

        $value = (float) $value;
        $addendum = $value > (float) $context->assay->max_count ? '<sup>*</sup>' : '';

        return $this->readyResult($context, [self::REPORT_FIELD => $this->formatValue($value).$addendum], $value);
    }

    private function formatValue(float $value): string
    {
        return rtrim(rtrim(sprintf('%.2F', $value), '0'), '.');
    }
}
