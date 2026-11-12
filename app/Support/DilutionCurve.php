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

    /** The legacy `-1=0.1` line for a dilution factor. */
    public static function line(float $dilution): string
    {
        $text = self::format($dilution);
        $decimals = explode('.', $text)[1] ?? null;

        return ($decimals === null ? '0' : '-'.strlen($decimals)).'='.$text;
    }
}
