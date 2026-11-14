<?php

namespace App\Calculations;

use App\Calculations\Concerns\BuildsKveResult;
use App\Calculations\Contracts\HandlesIncompleteResults;
use App\Calculations\Contracts\ResultCalculation;
use App\Models\Result;
use App\Support\DilutionCurve;

/**
 * Shared port of the legacy carcass swab scripts (`mazKarkasKiem`, `mazKarkasEntero`): a colony
 * count reported as log10 kve/cm² and classified against the (pig or other) carcass limits.
 */
abstract class MazKarkasCalculation implements HandlesIncompleteResults, ResultCalculation
{
    use BuildsKveResult;

    protected const REPORT_FIELD = 'kve';

    /** Legacy dilution factor per plate dilution, as listed in the original scripts. */
    protected const DILUTION_FACTORS = [
        '1' => 5,
        '0.1' => 50,
        '0.01' => 500,
        '0.001' => 5000,
        '0.0001' => 50000,
        '0.00001' => 500000,
    ];

    /** Plates retained (from the lowest dilution upward) for the calculation. */
    private const RETAINED_PLATES = 2;

    /** Whether the result is indicative when no retained plate reaches the minimum count. */
    protected const INDICATIVE_BELOW_MINIMUM = true;

    /** Whether the sum of two retained plates is divided by the 1.1 relative dilution weight. */
    protected const WEIGHTED_PLATE_SUM = true;

    /** Whether the estimate for an all-zero curve is indicative. */
    protected const INDICATIVE_MINIMUM_ESTIMATE = true;

    /**
     * The log10 classification limits of the script.
     *
     * @return array{lower: float, upper: float}
     */
    abstract protected function limitValues(bool $pig): array;

    public function calculate(ResultCalculationContext $context): array
    {
        $counts = $this->countsByDilution($context, 0);

        if ($counts === null) {
            return $this->failedResult($context);
        }

        $counts = array_map(fn (array $replicates): float|string => $replicates[0], $counts);

        if (! DilutionCurve::hasPlausibleDirection($counts)) {
            return $this->failedResult($context);
        }

        $minimum = (float) $context->assay->min_count;
        $maximum = (float) $context->assay->max_count;

        if ($this->allCountsAreZero($counts)) {
            return $this->successfulResult(
                $context,
                log10(5 / (float) array_key_first($counts)),
                static::INDICATIVE_MINIMUM_ESTIMATE,
                null,
            );
        }

        if ($this->allCountsAreAboveMaximum($counts, $maximum)) {
            $highestDilution = (string) array_key_last($counts);
            $count = $counts[$highestDilution] === '>' ? $maximum : $counts[$highestDilution];
            $factor = static::DILUTION_FACTORS[$highestDilution] ?? null;

            return $factor === null
                ? $this->failedResult($context)
                : $this->successfulResult($context, log10($count * $factor), true, $count * $factor);
        }

        $numericCounts = array_filter($counts, fn (float|string $count): bool => $count !== '>');

        if (! DilutionCurve::countsAreConsistent($numericCounts)) {
            return $this->failedResult($context);
        }

        $plates = $this->countablePlates($counts, $maximum);
        $factor = static::DILUTION_FACTORS[(string) array_key_first($plates)] ?? null;

        if ($plates === [] || $factor === null) {
            return $this->failedResult($context);
        }

        $colonies = 0.0;

        foreach ($plates as $dilution => $count) {
            $colonies += $context->applyConfirmationRatio($count, $dilution);
        }

        $relativeDilutionWeight = static::WEIGHTED_PLATE_SUM && count($plates) > 1 ? 1.1 : 1.0;
        $colonies = $colonies / $relativeDilutionWeight * $factor;
        $indicative = static::INDICATIVE_BELOW_MINIMUM && max($plates) < $minimum;

        return $this->successfulResult($context, log10($colonies), $indicative, $colonies);
    }

    /**
     * Counts per dilution (lowest dilution first), each keyed by replicate in ascending order.
     * Passing a replicate limits the counts to that replicate.
     *
     * @return array<string, array<int, float|string>>|null
     */
    protected function countsByDilution(ResultCalculationContext $context, ?int $replicate = null): ?array
    {
        $counts = [];
        $results = $context->analysis->results
            ->filter(fn (Result $result): bool => $replicate === null || (int) $result->rep === $replicate)
            ->sortBy([
                fn (Result $left, Result $right): int => (float) $right->df <=> (float) $left->df,
                fn (Result $left, Result $right): int => (int) $left->rep <=> (int) $right->rep,
            ]);

        foreach ($results as $result) {
            $dilution = (float) $result->df;
            $value = $result->data[self::REPORT_FIELD] ?? null;

            if ($dilution <= 0 || ($value !== '>' && (! is_numeric($value) || (float) $value < 0))) {
                return null;
            }

            $counts[DilutionCurve::format($dilution)][(int) $result->rep] = $value === '>' ? '>' : (float) $value;
        }

        return $counts === [] ? null : $counts;
    }

    /** @param  array<array-key, float|string>  $counts */
    protected function allCountsAreZero(array $counts): bool
    {
        return array_filter($counts, fn (float|string $count): bool => $count !== 0.0) === [];
    }

    /** @param  array<array-key, float|string>  $counts */
    protected function allCountsAreAboveMaximum(array $counts, float $maximum): bool
    {
        return array_filter($counts, fn (float|string $count): bool => $count !== '>' && $count <= $maximum) === [];
    }

    /**
     * The first plates, from the lowest dilution upward, with a count inside (0, maximum].
     *
     * @param  array<string, float|string>  $counts
     * @return array<string, float>
     */
    private function countablePlates(array $counts, float $maximum): array
    {
        $plates = array_filter(
            $counts,
            fn (float|string $count): bool => $count !== '>' && $count > 0 && $count <= $maximum,
        );

        return array_slice($plates, 0, self::RETAINED_PLATES, true);
    }

    /**
     * A reported result; a null colony count means the result cannot trigger a confirmation.
     */
    protected function successfulResult(
        ResultCalculationContext $context,
        float $logResult,
        bool $indicative,
        ?float $colonies,
    ): array {
        $formatted = number_format($logResult, 2, '.', '');
        $limits = $this->limits($context);
        $classification = match (true) {
            $logResult >= $limits['upper'] => $limits['high_text'],
            $logResult >= $limits['lower'] => $limits['medium_text'],
            default => $limits['lower_text'],
        };

        return $this->readyResult($context, [
            self::REPORT_FIELD => $formatted.($indicative ? '<sup>*</sup>' : ''),
            'addendum' => '('.$classification.')',
        ], $colonies, [
            'hidden' => [
                self::REPORT_FIELD => $formatted,
                'limits' => $limits,
                'indicatief' => $indicative,
            ],
            'confirmationPendingResult' => $this->notCompleted(),
        ]);
    }

    /**
     * Legacy truthiness: any non-empty `$varkens` value other than "0" selects the pig limits.
     *
     * @return array{lower: float, upper: float, lower_text: string, medium_text: string, high_text: string, lower_text_en: string, medium_text_en: string, high_text_en: string}
     */
    private function limits(ResultCalculationContext $context): array
    {
        return [
            ...$this->limitValues((bool) ($context->scriptVariables()['varkens'] ?? false)),
            'lower_text' => 'Toereikend',
            'medium_text' => 'Acceptabel',
            'high_text' => 'Ontoereikend',
            'lower_text_en' => 'Adequate',
            'medium_text_en' => 'Acceptable',
            'high_text_en' => 'Insufficient',
        ];
    }
}
