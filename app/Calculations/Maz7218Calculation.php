<?php

namespace App\Calculations;

use App\Calculations\Contracts\ResultCalculation;
use App\Models\SampleAnalysis;

class Maz7218Calculation implements ResultCalculation
{
    private const REPORT_FIELD = 'kve';

    private const INVALID_CURVE_MESSAGE = 'Onjuiste gegevens in de verdunningscurve.';

    private const P_VALUE_CUTOFF = 0.01;

    public function calculate(ResultCalculationContext $context): array
    {
        $analysis = $context->analysis;

        $counts = $this->countsByDilution($analysis);

        if ($counts === null || $counts === []) {
            return $this->failedResult();
        }

        if (! $this->curveIsValid($counts)) {
            return $this->failedResult();
        }

        $minimum = (float) $context->assay->min_count;
        $maximum = (float) $context->assay->max_count;

        if ($maximum <= 0 || $minimum < 0 || $minimum > $maximum) {
            return $this->failedResult();
        }

        if ($this->allCountsAre($counts, 0.0)) {
            $lowestDilution = (float) array_key_first($counts);

            return $this->successfulResult(
                '<'.$this->formatResult(1 / $lowestDilution, $lowestDilution),
                '-',
                1 / $lowestDilution,
                (int) $context->analysis->id,
            );
        }

        if ($this->allCountsAre($counts, '>')) {
            $highestDilution = (float) array_key_last($counts);

            return $this->successfulResult(
                '>'.$this->formatResult($maximum / $highestDilution, $highestDilution),
                null,
                $maximum / $highestDilution,
                (int) $context->analysis->id,
                [$this->indicativeAddendum()],
            );
        }

        $calculationCounts = $this->selectCalculationCounts(
            $counts,
            $minimum,
            $maximum,
        );

        if ($calculationCounts === []) {
            return $this->failedResult();
        }

        $firstDilution = (float) array_key_first($calculationCounts);
        $colonyCount = 0.0;

        foreach ($calculationCounts as $dilution => $replicates) {
            $adjustedReplicates = [];

            foreach ($replicates as $replicate => $value) {
                $adjustedReplicates[] = $context->applyConfirmationRatio($value, $dilution, $replicate);
            }

            $colonyCount += array_sum($adjustedReplicates) / count($adjustedReplicates);
        }

        $relativeDilutionWeight = count($calculationCounts) > 1 ? 1.1 : 1.0;
        $result = round($colonyCount / ($relativeDilutionWeight * $firstDilution));
        $addenda = $this->resultIsIndicative($counts, $minimum, $maximum)
            ? [$this->indicativeAddendum()]
            : [];

        return $this->successfulResult(
            $this->formatResult($result, $firstDilution),
            null,
            (float) $result,
            (int) $context->analysis->id,
            $addenda,
        );
    }

    /**
     * @return array<string, array<int, float|string>>|null
     */
    private function countsByDilution(SampleAnalysis $analysis): ?array
    {
        $counts = [];

        foreach ($analysis->results->sortByDesc(fn ($result): float => (float) $result->df) as $result) {
            $dilution = (float) $result->df;
            $replicate = (int) $result->rep;
            $value = $result->data[self::REPORT_FIELD] ?? null;

            if ($dilution <= 0 || ($value !== '>' && (! is_numeric($value) || (float) $value < 0))) {
                return null;
            }

            $counts[$this->dilutionKey($dilution)][$replicate] = $value === '>' ? '>' : (float) $value;
        }

        return $counts;
    }

    /**
     * @param  array<string, array<int, float|string>>  $counts
     */
    private function curveIsValid(array $counts): bool
    {
        $averages = [];

        foreach ($counts as $dilution => $replicates) {
            if (in_array('>', $replicates, true)) {
                if (count(array_unique($replicates, SORT_REGULAR)) !== 1) {
                    return false;
                }

                $averages[$dilution] = '>';

                continue;
            }

            if (! $this->replicatesAreConsistent($replicates)) {
                return false;
            }

            $averages[$dilution] = array_sum($replicates) / count($replicates);
        }

        if (! $this->hasPlausibleDirection($averages)) {
            return false;
        }

        $numericAverages = array_filter($averages, fn (float|string $value): bool => $value !== '>');

        return $this->dilutionsAreConsistent($numericAverages);
    }

    /**
     * @param  list<float>  $replicates
     */
    private function replicatesAreConsistent(array $replicates): bool
    {
        for ($left = 0; $left < count($replicates); $left++) {
            for ($right = $left + 1; $right < count($replicates); $right++) {
                $high = max($replicates[$left], $replicates[$right]);
                $low = min($replicates[$left], $replicates[$right]);

                if ($high === 0.0) {
                    continue;
                }

                $mean = ($high + $low) / 2;
                $chiSquare = 2 * $high * log($high / $mean);

                if ($low > 0) {
                    $chiSquare += 2 * $low * log($low / $mean);
                }

                if ($this->chiSquareSurvival($chiSquare) < self::P_VALUE_CUTOFF) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * @param  array<string, float|string>  $averages
     */
    private function hasPlausibleDirection(array $averages): bool
    {
        $previous = null;

        foreach ($averages as $value) {
            if ($previous === null) {
                $previous = $value;

                continue;
            }

            if ((is_float($previous) && $value === '>') || ($previous === '>' && $value === 0.0)) {
                return false;
            }

            if ($previous !== '>' && $value !== '>' && max($value, $previous) > 0 && $value >= $previous) {
                return false;
            }

            $previous = $value;
        }

        return true;
    }

    /**
     * @param  array<string, float>  $averages
     */
    private function dilutionsAreConsistent(array $averages): bool
    {
        $values = array_values($averages);

        for ($index = 1; $index < count($values); $index++) {
            $previous = $values[$index - 1];
            $current = $values[$index];

            if ($previous === 0.0 && $current === 0.0) {
                break;
            }

            $total = $previous + $current;
            $chiSquare = $current === 0.0
                ? 2 * $previous * log($previous / (10 * $total / 11))
                : 2 * (
                    $previous * log($previous / (10 * $total / 11))
                    + $current * log($current / ($total / 11))
                );

            if ($this->chiSquareSurvival($chiSquare) < self::P_VALUE_CUTOFF) {
                return false;
            }

            if ($current === 0.0) {
                break;
            }
        }

        return true;
    }

    /**
     * @param  array<string, array<int, float|string>>  $counts
     * @return array<string, array<int, float>>
     */
    private function selectCalculationCounts(array $counts, float $minimum, float $maximum): array
    {
        $numeric = [];

        foreach ($counts as $dilution => $replicates) {
            $values = array_filter($replicates, fn (float|string $value): bool => $value !== '>');

            if ($values !== []) {
                $numeric[$dilution] = $values;
            }
        }

        $dilutions = array_keys($numeric);
        $selected = [];
        $foundCountableDilution = false;

        foreach ($dilutions as $index => $dilution) {
            $values = $numeric[$dilution];
            $allCountable = count(array_filter(
                $values,
                fn (float $value): bool => $value >= $minimum && $value <= $maximum,
            )) === count($values);

            if ($foundCountableDilution || $allCountable) {
                $selected[$dilution] = $values;
                $foundCountableDilution = true;
            } else {
                $nextValues = isset($dilutions[$index + 1]) ? $numeric[$dilutions[$index + 1]] : [];
                $nextCanRescue = $nextValues !== [] && array_sum($nextValues) > 0 && count(array_filter(
                    $nextValues,
                    fn (float $value): bool => $value >= $minimum && $value <= $maximum,
                )) > 0;

                if (! $nextCanRescue) {
                    $selected[$dilution] = $values;
                }
            }

            if (isset($dilutions[$index + 1]) && array_sum($numeric[$dilutions[$index + 1]]) === 0.0) {
                break;
            }
        }

        return $selected;
    }

    /**
     * @param  array<string, array<int, float|string>>  $counts
     */
    private function allCountsAre(array $counts, float|string $expected): bool
    {
        foreach ($counts as $replicates) {
            foreach ($replicates as $value) {
                if ($value !== $expected) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * The legacy calculator marks a result as indicative when no dilution has
     * a complete set of counts inside the assay limits.
     *
     * @param  array<string, array<int, float|string>>  $counts
     */
    private function resultIsIndicative(array $counts, float $minimum, float $maximum): bool
    {
        foreach ($counts as $replicates) {
            if ($replicates !== [] && count(array_filter(
                $replicates,
                fn (float|string $value): bool => $value !== '>' && $value >= $minimum && $value <= $maximum,
            )) === count($replicates)) {
                return false;
            }
        }

        return true;
    }

    /** @return array{code: string, label: string} */
    private function indicativeAddendum(): array
    {
        return ['code' => 'indicative', 'label' => 'indicatieve waarde'];
    }

    private function chiSquareSurvival(float $chiSquare): float
    {
        $probability = exp(-0.5 * $chiSquare) * sqrt(2 * $chiSquare / pi());
        $term = $probability;
        $degrees = 1;

        while ($term > 1e-15 * $probability) {
            $degrees += 2;
            $term *= $chiSquare / $degrees;
            $probability += $term;
        }

        return 1 - $probability;
    }

    private function formatResult(float $result, float $firstDilution): string
    {
        $significantFigures = $firstDilution === 1.0 || $result >= 100 ? 2 : 1;
        $decimalPlaces = (int) floor($significantFigures - log10(abs($result)));
        $rounded = round($result, $decimalPlaces);

        return number_format($rounded, 0, ',', '.');
    }

    private function dilutionKey(float $dilution): string
    {
        return rtrim(rtrim(sprintf('%.10F', $dilution), '0'), '.');
    }

    private function successfulResult(
        string $result,
        ?string $disposition = null,
        ?float $numericValue = null,
        ?int $targetAnalysisId = null,
        array $addenda = [],
    ): array {
        return $this->resultPayload($result, true, $disposition, [], $numericValue, $targetAnalysisId, $addenda);
    }

    private function failedResult(): array
    {
        return $this->resultPayload(
            'Fout resultaat gevonden',
            false,
            null,
            [self::INVALID_CURVE_MESSAGE],
        );
    }

    private function resultPayload(
        string $result,
        bool $isReady,
        ?string $disposition = null,
        array $messages = [],
        ?float $numericValue = null,
        ?int $targetAnalysisId = null,
        array $addenda = [],
    ): array {
        $isBoundedResult = str_starts_with($result, '<') || str_starts_with($result, '>');

        $payload = [
            'output' => [self::REPORT_FIELD => $result],
            'messageBag' => $messages,
            'reportIn' => self::REPORT_FIELD,
            'outputEn' => [],
            'disposition' => $disposition === null ? [] : [self::REPORT_FIELD => $disposition],
            'isReady' => $isReady,
            'resultMask' => [self::REPORT_FIELD => self::REPORT_FIELD],
            'resultHide' => [],
            'confirmationTrigger' => [
                'eligible' => $isReady && $numericValue !== null && $numericValue > 0 && $disposition !== '-' && ! $isBoundedResult,
                'numericValue' => $numericValue,
                'disposition' => $disposition,
                'targetAnalysisId' => $targetAnalysisId,
            ],
        ];

        if ($addenda !== []) {
            $payload['addenda'] = $addenda;
        }

        return $payload;
    }
}
