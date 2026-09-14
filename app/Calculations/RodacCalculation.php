<?php

namespace App\Calculations;

use App\Calculations\Contracts\ResultCalculation;
use App\Models\Result;

class RodacCalculation implements ResultCalculation
{
    private const REPORT_FIELD = 'kve';

    public function calculate(ResultCalculationContext $context): array
    {
        $result = $context->analysis->results->first(
            fn (Result $result): bool => (float) $result->df === 1.0,
        );
        $value = $result?->data[self::REPORT_FIELD] ?? null;

        if (! is_numeric($value)) {
            return $this->resultPayload('Fout resultaat gevonden', false);
        }

        $value = (float) $value;
        $addendum = $value > (float) $context->assay->max_count ? '<sup>*</sup>' : '';

        return $this->resultPayload($this->formatValue($value).$addendum, true, $value, (int) $context->analysis->id);
    }

    private function formatValue(float $value): string
    {
        return rtrim(rtrim(sprintf('%.2F', $value), '0'), '.');
    }

    private function resultPayload(string $result, bool $isReady, ?float $numericValue = null, ?int $targetAnalysisId = null): array
    {
        return [
            'output' => [self::REPORT_FIELD => $result],
            'messageBag' => [],
            'reportIn' => self::REPORT_FIELD,
            'outputEn' => [],
            'disposition' => [],
            'isReady' => $isReady,
            'resultMask' => [self::REPORT_FIELD => self::REPORT_FIELD],
            'resultHide' => [],
            'confirmationTrigger' => [
                'eligible' => $isReady && $numericValue !== null && $numericValue > 0,
                'numericValue' => $numericValue,
                'disposition' => null,
                'targetAnalysisId' => $targetAnalysisId,
            ],
        ];
    }
}
