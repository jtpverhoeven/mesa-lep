<?php

namespace App\Support;

/**
 * Rules for dilution factors (the legacy `df` values: 1, 0.1, 0.01, ...).
 *
 * A usable curve is a run of consecutive decades; skipping one (-1 and -3) makes
 * the calculations meaningless.
 */
final class DilutionCurve
{
    public const NOT_A_DECADE_MESSAGE = 'Verdunningen moeten machten van tien zijn (1, 0.1, 0.01, ...).';

    public const NOT_SEQUENTIAL_MESSAGE = 'Verdunningen moeten op elkaar aansluiten, bijvoorbeeld -1, -2 en -3.';

    public const P_VALUE_CUTOFF = 0.01;

    /** The legacy text form of a dilution factor, without scientific notation. */
    public static function format(float $dilution): string
    {
        return rtrim(rtrim(sprintf('%.10F', $dilution), '0'), '.');
    }

    /** The power of ten of a dilution factor (0.01 is -2), or null when it is not an exact decade. */
    public static function exponent(string|int|float|null $dilution): ?int
    {
        if (! is_numeric($dilution) || (float) $dilution <= 0) {
            return null;
        }

        $logarithm = log10((float) $dilution);
        $exponent = (int) round($logarithm);

        return abs($logarithm - $exponent) < 1e-9 ? $exponent : null;
    }

    /**
     * @param  array<mixed>  $dilutions
     */
    public static function isSequential(array $dilutions): bool
    {
        $exponents = [];

        foreach ($dilutions as $dilution) {
            $exponent = self::exponent($dilution);

            if ($exponent === null) {
                return false;
            }

            $exponents[$exponent] = true;
        }

        return $exponents === [] || max(array_keys($exponents)) - min(array_keys($exponents)) + 1 === count($exponents);
    }

    /**
     * Why a set of dilution factors entered by a user cannot be used, or null when it can.
     *
     * @param  array<mixed>  $dilutions
     */
    public static function problem(array $dilutions): ?string
    {
        foreach ($dilutions as $dilution) {
            if (self::exponent($dilution) === null || (float) $dilution > 1) {
                return self::NOT_A_DECADE_MESSAGE;
            }
        }

        return self::isSequential($dilutions) ? null : self::NOT_SEQUENTIAL_MESSAGE;
    }

    /**
     * Whether colony counts, ordered from the lowest to the highest dilution, fall as the
     * dilution rises. `>` may only be followed by `>` or a count, and never by a zero.
     *
     * @param  array<array-key, float|string>  $counts
     */
    public static function hasPlausibleDirection(array $counts): bool
    {
        $previous = null;

        foreach ($counts as $value) {
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
     * Whether consecutive numeric counts (ordered from the lowest to the highest dilution)
     * match the expected tenfold drop, using the legacy chi-square test with p < 0.01.
     *
     * @param  array<array-key, float>  $counts
     */
    public static function countsAreConsistent(array $counts): bool
    {
        $values = array_values($counts);

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

            if (self::chiSquareSurvival($chiSquare) < self::P_VALUE_CUTOFF) {
                return false;
            }

            if ($current === 0.0) {
                break;
            }
        }

        return true;
    }

    /** Whether two replicate counts of one dilution agree, using the legacy chi-square test with p < 0.01. */
    public static function replicatePairIsConsistent(float $first, float $second): bool
    {
        $high = max($first, $second);
        $low = min($first, $second);

        if ($high === 0.0) {
            return true;
        }

        $mean = ($high + $low) / 2;
        $chiSquare = 2 * $high * log($high / $mean);

        if ($low > 0) {
            $chiSquare += 2 * $low * log($low / $mean);
        }

        return self::chiSquareSurvival($chiSquare) >= self::P_VALUE_CUTOFF;
    }

    /** The upper-tail probability of a chi-square value with one degree of freedom. */
    public static function chiSquareSurvival(float $chiSquare): float
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

    /** The legacy `-1=0.1` line for a dilution factor. */
    public static function line(float $dilution): string
    {
        $text = self::format($dilution);
        $decimals = explode('.', $text)[1] ?? null;

        return ($decimals === null ? '0' : '-'.strlen($decimals)).'='.$text;
    }
}
