<?php

namespace App\Calculations;

use App\Calculations\Concerns\BuildsKveResult;
use App\Calculations\Contracts\HandlesIncompleteResults;
use App\Calculations\Contracts\ResultCalculation;
use App\Models\SampleAnalysis;
use App\Support\DilutionCurve;

class Maz7218Calculation implements HandlesIncompleteResults, ResultCalculation
{
    use BuildsKveResult;

    private const REPORT_FIELD = 'kve';

    private const INVALID_CURVE_MESSAGE = 'Onjuiste gegevens in de verdunningscurve.';

    private const NON_SEQUENTIAL_CURVE_MESSAGE = 'Onjuiste verdunningscurve: de verdunningen moeten op elkaar aansluiten, bijvoorbeeld -1, -2 en -3.';

    public function calculate(ResultCalculationContext $context): array
    {
        $analysis = $context->analysis;

        $counts = $this->countsByDilution($analysis);

        if ($counts === null || $counts === []) {
            return $this->invalidCurveResult($context);
        }

        if (! DilutionCurve::isSequential(array_keys($counts))) {
            return $this->invalidCurveResult($context, self::NON_SEQUENTIAL_CURVE_MESSAGE);
        }

        if (! $this->curveIsValid($counts)) {
            return $this->invalidCurveResult($context);
        }

        $minimum = (float) $context->assay->min_count;
        $maximum = (float) $context->assay->max_count;

        if ($maximum <= 0 || $minimum < 0 || $minimum > $maximum) {
            return $this->invalidCurveResult($context);
        }

        if ($this->allCountsAre($counts, 0.0)) {
            $lowestDilution = (float) array_key_first($counts);

            return $this->successfulResult(
                $context,
                '<'.$this->formatResult(1 / $lowestDilution, $lowestDilution),
                '-',
                1 / $lowestDilution,
            );
        }

        if ($this->allCountsAre($counts, '>')) {
            $highestDilution = (float) array_key_last($counts);

            return $this->successfulResult(
                $context,
                '>'.$this->formatResult($maximum / $highestDilution, $highestDilution),
                null,
                $maximum / $highestDilution,
                [$this->indicativeAddendum()],
            );
        }

        $calculationCounts = $this->selectCalculationCounts(
            $counts,
            $minimum,
            $maximum,
        );

        if ($calculationCounts === []) {
            return $this->invalidCurveResult($context);
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
            $context,
            $this->formatResult($result, $firstDilution),
            null,
            (float) $result,
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

            $counts[DilutionCurve::format($dilution)][$replicate] = $value === '>' ? '>' : (float) $value;
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

        if (! DilutionCurve::hasPlausibleDirection($averages)) {
            return false;
        }

        $numericAverages = array_filter($averages, fn (float|string $value): bool => $value !== '>');

        return DilutionCurve::countsAreConsistent($numericAverages);
    }

    /**
     * @param  list<float>  $replicates
     */
    private function replicatesAreConsistent(array $replicates): bool
    {
        for ($left = 0; $left < count($replicates); $left++) {
            for ($right = $left + 1; $right < count($replicates); $right++) {
                if (! DilutionCurve::replicatePairIsConsistent($replicates[$left], $replicates[$right])) {
                    return false;
                }
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

    private function formatResult(float $result, float $firstDilution): string
    {
        return $this->significantFigures($result, $firstDilution !== 1.0);
    }

    private function successfulResult(
        ResultCalculationContext $context,
        string $result,
        ?string $disposition,
        float $numericValue,
        array $addenda = [],
    ): array {
        $isBoundedResult = str_starts_with($result, '<') || str_starts_with($result, '>');

        return $this->readyResult(
            $context,
            [self::REPORT_FIELD => $result],
            $numericValue,
            $addenda === [] ? [] : ['addenda' => $addenda],
            $disposition !== '-' && ! $isBoundedResult,
            $disposition,
        );
    }

    private function invalidCurveResult(ResultCalculationContext $context, string $message = self::INVALID_CURVE_MESSAGE): array
    {
        return $this->failedResult($context, [$message]);
    }
}
