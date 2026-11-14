<?php

namespace App\Calculations;

use App\Calculations\Concerns\BuildsKveResult;
use App\Calculations\Contracts\HandlesIncompleteResults;
use App\Calculations\Contracts\ResultCalculation;
use App\Models\Result;
use App\Support\DilutionCurve;

/**
 * Port of the legacy `mazSchelp` script for shellfish.
 *
 * Replicate assays sum every count and multiply it by 20. Without replicates the first two countable
 * dilutions are used. With confirmation, results are `<sup>**</sup>` (unconfirmed) until the confirmation
 * workflow is complete; its ratios replace the legacy racetrack factor.
 */
class MazSchelpCalculation implements HandlesIncompleteResults, ResultCalculation
{
    use BuildsKveResult;

    private const REPLICATE_FACTOR = 20;

    private const LOWEST_POSSIBLE_RESULT = '<20';

    private const INDICATIVE = '<sup>*</sup>';

    private const UNCONFIRMED = '<sup>**</sup>';

    private const RETAINED_PLATES = 2;

    public function calculate(ResultCalculationContext $context): array
    {
        $rows = $this->rows($context);

        if ($rows === []) {
            return $this->failedResult($context);
        }

        return (int) $context->assay->replicates === 1
            ? $this->calculateReplicates($context, $rows)
            : $this->calculateDilutions($context, $rows);
    }

    /**
     * @param  list<array{dilution: string, replicate: int, count: mixed}>  $rows
     */
    private function calculateReplicates(ResultCalculationContext $context, array $rows): array
    {
        if ($this->allCountsAreZero(array_column($rows, 'count'))) {
            return $this->result($context, self::LOWEST_POSSIBLE_RESULT, null, '-');
        }

        $sum = 0.0;
        $confirmedSum = 0.0;

        foreach ($rows as $row) {
            if (is_numeric($row['count'])) {
                $sum += (float) $row['count'];
                $confirmedSum += (float) $row['count'] * ($context->confirmationRatio($row['dilution'], $row['replicate']) ?? 1.0);
            }
        }

        $minimum = (float) $context->assay->min_count;
        $result = $sum * self::REPLICATE_FACTOR;

        if (! $this->usesConfirmation($context)) {
            $indicative = $sum < $minimum || $sum > (float) $context->assay->max_count;

            return $this->result($context, $this->significantFigures($result).($indicative ? self::INDICATIVE : ''), $result);
        }

        $indicative = $sum < $minimum ? self::INDICATIVE : '';

        if (! $this->isConfirmed($context)) {
            [$reported, $disposition] = $this->orLowestPossible($result == 0.0, (string) $result);
            $markers = $indicative === '' ? self::UNCONFIRMED : $indicative.' '.self::UNCONFIRMED;

            return $this->result($context, $reported.$markers, $result, $disposition, awaitsConfirmation: true);
        }

        $confirmedResult = $confirmedSum * self::REPLICATE_FACTOR;
        [$reported, $disposition] = $this->orLowestPossible($confirmedResult == 0.0, (string) $confirmedResult);

        return $this->result($context, $reported.$indicative, $result, $disposition);
    }

    /**
     * @param  list<array{dilution: string, replicate: int, count: mixed}>  $rows
     */
    private function calculateDilutions(ResultCalculationContext $context, array $rows): array
    {
        $counts = [];

        foreach ($rows as $row) {
            if ($row['replicate'] !== 0) {
                continue;
            }

            if ($row['count'] !== '>' && ! is_numeric($row['count'])) {
                return $this->failedResult($context);
            }

            $counts[$row['dilution']] = $row['count'] === '>' ? '>' : (float) $row['count'];
        }

        if ($counts === []) {
            return $this->failedResult($context);
        }

        $maximum = (float) $context->assay->max_count;
        $lowestDilution = (float) array_key_first($counts);

        if ($this->allCountsAreZero($counts)) {
            return $this->result($context, '<'.(1 / $lowestDilution), null);
        }

        if ($this->allCountsAreAboveMaximum($counts, $maximum)) {
            return $this->aboveMaximumResult($context, $counts, $maximum);
        }

        $plates = array_slice(array_filter(
            $counts,
            fn (float|string $count): bool => $count !== '>' && $count > 0 && $count <= $maximum,
        ), 0, self::RETAINED_PLATES, true);

        if ($plates === []) {
            return $this->failedResult($context);
        }

        $messages = $this->plateMessages($plates);
        $autoAdjust = $lowestDilution !== 1.0;
        $indicative = $this->foundAboveMinimum($rows, (float) $context->assay->min_count) ? '' : self::INDICATIVE;
        $result = $this->platesResult($context, $plates, false);
        $reported = $this->significantFigures($result, $autoAdjust);

        if (! $this->usesConfirmation($context)) {
            return $this->result($context, $reported.$indicative, $result, messages: $messages);
        }

        $twoPlates = count($plates) === self::RETAINED_PLATES;

        if (! $this->isConfirmed($context)) {
            [$reported, $disposition] = $this->orLowestPossible($reported === '0', $reported);
            $markers = $twoPlates || $disposition !== null || $indicative === ''
                ? self::UNCONFIRMED
                : $indicative.' '.self::UNCONFIRMED;

            return $this->result($context, $reported.$markers, $result, $disposition, $messages, true);
        }

        $confirmed = $this->significantFigures($this->platesResult($context, $plates, true), $autoAdjust);
        [$reported, $disposition] = $this->orLowestPossible($confirmed === '0', $confirmed);

        return $this->result($context, $reported.($twoPlates ? '' : $indicative), $result, $disposition, $messages);
    }

    /**
     * @param  array<string, float|string>  $counts
     */
    private function aboveMaximumResult(ResultCalculationContext $context, array $counts, float $maximum): array
    {
        $highestDilution = (string) array_key_last($counts);
        $indicator = $counts[$highestDilution] === '>' ? '>' : '';
        $result = ($indicator === '>' ? $maximum : $counts[$highestDilution]) / (float) $highestDilution;

        if (! $this->usesConfirmation($context)) {
            return $this->result($context, $indicator.$this->significantFigures($result).'*', $result);
        }

        if (! $this->isConfirmed($context)) {
            $reported = $indicator.$this->significantFigures($result).self::INDICATIVE.' '.self::UNCONFIRMED.' ';

            return $this->result($context, $reported, $result, awaitsConfirmation: true);
        }

        $confirmed = $result * $context->confirmationRatio($highestDilution, 0);
        $reported = $confirmed == 0.0
            ? '<'.(1 / (float) $highestDilution)
            : $indicator.$this->significantFigures($confirmed).self::INDICATIVE;

        return $this->result($context, $reported, $result);
    }

    /**
     * Legacy one- and two-plate calculation, optionally weighting each plate by its confirmation ratio.
     *
     * @param  array<string, float>  $plates
     */
    private function platesResult(ResultCalculationContext $context, array $plates, bool $confirmed): float
    {
        $sum = 0.0;

        foreach ($plates as $dilution => $count) {
            $sum += $count * ($confirmed ? $context->confirmationRatio($dilution, 0) : 1.0);
        }

        $relativeDilutionWeight = count($plates) === self::RETAINED_PLATES ? 1.1 : 1.0;

        return $sum / ($relativeDilutionWeight * (float) array_key_first($plates));
    }

    /**
     * Legacy warns, without rejecting the result, when the second plate deviates too much from the first.
     *
     * @param  array<string, float>  $plates
     * @return list<string>
     */
    private function plateMessages(array $plates): array
    {
        if (count($plates) < 2) {
            return [];
        }

        [$firstDilution, $secondDilution] = array_map('strval', array_keys($plates));
        $first = $plates[$firstDilution];
        $corrected = $plates[$secondDilution] * 10;
        $lowest = min($first, $corrected);
        $allowedDeviation = ($first + $corrected) / 2 > 30 ? 0.5 : 1.0;

        return max($first, $corrected) > $lowest + $lowest * $allowedDeviation
            ? ["Verdacht resultaat, verschil te groot tussen verdunning ({$firstDilution}) en ({$secondDilution})"]
            : [];
    }

    /**
     * Legacy compares every count, of every dilution and replicate, with the minimum; `>` counts as above it.
     *
     * @param  list<array{dilution: string, replicate: int, count: mixed}>  $rows
     */
    private function foundAboveMinimum(array $rows, float $minimum): bool
    {
        foreach ($rows as $row) {
            if ($row['count'] === '>' || (is_numeric($row['count']) && (float) $row['count'] >= $minimum)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  array<array-key, mixed>  $counts
     */
    private function allCountsAreZero(array $counts): bool
    {
        foreach ($counts as $count) {
            if (! is_numeric($count) || (float) $count != 0.0) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param  array<string, float|string>  $counts
     */
    private function allCountsAreAboveMaximum(array $counts, float $maximum): bool
    {
        foreach ($counts as $count) {
            if ($count !== '>' && $count <= $maximum) {
                return false;
            }
        }

        return true;
    }

    /**
     * Legacy replaces a zero result by `<20` with a negative disposition.
     *
     * @return array{string, ?string}
     */
    private function orLowestPossible(bool $isZero, string $reported): array
    {
        return $isZero ? [self::LOWEST_POSSIBLE_RESULT, '-'] : [$reported, null];
    }

    private function usesConfirmation(ResultCalculationContext $context): bool
    {
        return (int) $context->assay->confirmation === 1;
    }

    private function isConfirmed(ResultCalculationContext $context): bool
    {
        return $context->confirmation?->isComplete() === true;
    }

    /**
     * @param  list<string>  $messages
     */
    private function result(
        ResultCalculationContext $context,
        string $reported,
        ?float $numericValue,
        ?string $disposition = null,
        array $messages = [],
        bool $awaitsConfirmation = false,
    ): array {
        $overrides = ['messageBag' => $messages];

        // Legacy keeps showing the unconfirmed result while the confirmation is pending.
        if ($awaitsConfirmation) {
            $overrides['confirmationPendingResult'] = [
                'output' => ['kve' => $reported],
                'outputEn' => [],
                'disposition' => $disposition === null ? [] : ['kve' => $disposition],
            ];
        }

        return $this->readyResult($context, ['kve' => $reported], $numericValue, $overrides, disposition: $disposition);
    }

    /**
     * Every count, ordered from the lowest to the highest dilution and by replicate.
     *
     * @return list<array{dilution: string, replicate: int, count: mixed}>
     */
    private function rows(ResultCalculationContext $context): array
    {
        return $context->analysis->results
            ->sortBy([
                fn (Result $left, Result $right): int => (float) $right->df <=> (float) $left->df,
                fn (Result $left, Result $right): int => (int) $left->rep <=> (int) $right->rep,
            ])
            ->map(fn (Result $result): array => [
                'dilution' => DilutionCurve::format((float) $result->df),
                'replicate' => (int) $result->rep,
                'count' => $result->data['kve'] ?? null,
            ])
            ->values()
            ->all();
    }
}
