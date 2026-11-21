<?php

namespace App\Support;

use App\AssuranceForms\AssuranceValueAssessment;
use Carbon\CarbonImmutable;

class LegacyTimestamp
{
    /** Formats a legacy unix timestamp column in the laboratory timezone; null when it holds no usable timestamp. */
    public static function format(mixed $value, string $format = 'd-m-Y'): ?string
    {
        if (! is_numeric($value) || (int) $value <= 0) {
            return null;
        }

        return CarbonImmutable::createFromTimestamp((int) $value, AssuranceValueAssessment::TIMEZONE)->format($format);
    }
}
