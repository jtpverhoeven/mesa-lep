<?php

namespace App\Calculations\Concerns;

use App\Calculations\ResultCalculationContext;
use App\Models\Result;

/**
 * The legacy result payload of scripts that report in the `kve` field, including their
 * "Niet afgerond" result while not every result field has been filled in.
 */
trait BuildsKveResult
{
    public function allowsEmptyResultField(array $data, string $field): bool
    {
        return false;
    }

    public function incompleteResult(ResultCalculationContext $context): array
    {
        return $this->resultPayload($context, $this->notCompleted());
    }

    /**
     * The legacy "Niet afgerond" output, also shown while a confirmation is pending.
     *
     * @param  array<string, string>  $disposition
     * @return array{output: array{kve: string}, outputEn: array{kve: string}, disposition: array<string, string>}
     */
    protected function notCompleted(array $disposition = []): array
    {
        return [
            'output' => ['kve' => 'Niet afgerond'],
            'outputEn' => ['kve' => 'Not completed'],
            'disposition' => $disposition,
        ];
    }

    /**
     * A result that is not ready because the entered results cannot be reported.
     *
     * @param  list<string>  $messages
     */
    protected function failedResult(
        ResultCalculationContext $context,
        array $messages = [],
        string $result = 'Fout resultaat gevonden',
    ): array {
        return $this->resultPayload($context, [
            'output' => ['kve' => $result],
            'messageBag' => $messages,
        ]);
    }

    /**
     * The fields of the undiluted (df 1) first replicate, which legacy scripts read as `$this->results[1]`.
     *
     * @return array<string, mixed>|null
     */
    protected function undilutedData(ResultCalculationContext $context): ?array
    {
        $data = $context->analysis->results
            ->first(fn (Result $result): bool => (float) $result->df === 1.0 && (int) $result->rep === 0)
            ?->data;

        return is_array($data) ? $data : null;
    }

    /**
     * A ready result. It can trigger a confirmation when it is confirmable and its numeric value is positive.
     */
    protected function readyResult(
        ResultCalculationContext $context,
        array $output,
        ?float $numericValue,
        array $overrides = [],
        bool $confirmable = true,
        ?string $disposition = null,
    ): array {
        return $this->resultPayload($context, [
            'output' => $output,
            'disposition' => $disposition === null ? [] : ['kve' => $disposition],
            'isReady' => true,
            'calculationReady' => true,
            'confirmationTrigger' => [
                'eligible' => $confirmable && $numericValue !== null && $numericValue > 0,
                'numericValue' => $numericValue,
                'disposition' => $disposition,
                'targetAnalysisId' => (int) $context->analysis->id,
            ],
            ...$overrides,
        ]);
    }

    /**
     * Legacy `format_number_significant_figures()`: rounds to significant figures (one below 100 when
     * auto-adjusting) and formats without decimals in the Dutch notation.
     */
    protected function significantFigures(float $number, bool $autoAdjust = true, int $figures = 2): string
    {
        if ($number == 0.0) {
            return '0';
        }

        if ($autoAdjust && $number < 100) {
            $figures = 1;
        }

        $decimalPlaces = (int) floor($figures - log10(abs($number)));

        return number_format(round($number, $decimalPlaces), 0, ',', '.');
    }

    protected function resultPayload(ResultCalculationContext $context, array $overrides): array
    {
        return array_replace([
            'output' => [],
            'messageBag' => [],
            'reportIn' => 'kve',
            'outputEn' => [],
            'disposition' => [],
            'isReady' => false,
            'calculationReady' => false,
            'resultMask' => ['kve' => $context->reportLabel('kve')],
            'resultHide' => [],
            'confirmationTrigger' => [
                'eligible' => false,
                'numericValue' => null,
                'disposition' => null,
                'targetAnalysisId' => (int) $context->analysis->id,
            ],
        ], $overrides);
    }
}
