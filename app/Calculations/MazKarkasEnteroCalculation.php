<?php

namespace App\Calculations;

use App\Support\DilutionCurve;

/**
 * Port of the legacy `mazKarkasEntero` script: the Enterobacteriaceae count of a carcass swab.
 *
 * Replicate assays average the replicates of the lowest dilution. Without replicates the shared
 * dilution curve calculation applies with the legacy quirks of this script: the sum of two plates
 * is not weighted, and neither low counts nor the all-zero estimate are marked indicative.
 */
class MazKarkasEnteroCalculation extends MazKarkasCalculation
{
    protected const INDICATIVE_BELOW_MINIMUM = false;

    protected const WEIGHTED_PLATE_SUM = false;

    protected const INDICATIVE_MINIMUM_ESTIMATE = false;

    public function calculate(ResultCalculationContext $context): array
    {
        return (int) $context->assay->replicates === 1
            ? $this->calculateReplicates($context)
            : parent::calculate($context);
    }

    protected function limitValues(bool $pig): array
    {
        return $pig
            ? ['lower' => 2.00, 'upper' => 3.00]
            : ['lower' => 1.50, 'upper' => 2.50];
    }

    private function calculateReplicates(ResultCalculationContext $context): array
    {
        $counts = $this->countsByDilution($context);

        if ($counts === null) {
            return $this->failedResult($context);
        }

        $allCounts = array_merge(...array_values($counts));

        if ($this->allCountsAreZero($allCounts)) {
            return $this->successfulResult($context, 0.0, false, null);
        }

        $minimum = (float) $context->assay->min_count;
        $maximum = (float) $context->assay->max_count;
        $dilution = (string) array_key_first($counts);
        $factor = static::DILUTION_FACTORS[$dilution] ?? null;
        $plates = array_values($counts[$dilution]);

        if ($factor === null) {
            return $this->failedResult($context);
        }

        // Legacy only tests the first two replicates against each other.
        if (count($plates) > 1 && $plates[0] !== '>' && $plates[1] !== '>'
            && ! DilutionCurve::replicatePairIsConsistent($plates[0], $plates[1])) {
            return $this->failedResult($context);
        }

        $colonies = 0.0;

        foreach ($plates as $replicate => $count) {
            $colonies += $context->applyConfirmationRatio($count === '>' ? $maximum : $count, $dilution, $replicate);
        }

        $colonies = $colonies / count($plates) * $factor;
        $indicative = $this->allCountsAreAboveMaximum($allCounts, $maximum)
            || ! $this->isMajority($plates, fn (float|string $count): bool => $count === '>' || $count >= $minimum)
            || ! $this->isMajority($plates, fn (float|string $count): bool => $count !== '>' && $count <= $maximum);

        return $this->successfulResult($context, log10($colonies), $indicative, $colonies);
    }

    /**
     * @param  list<float|string>  $counts
     * @param  callable(float|string): bool  $matches
     */
    private function isMajority(array $counts, callable $matches): bool
    {
        return count(array_filter($counts, $matches)) / count($counts) > 0.5;
    }
}
