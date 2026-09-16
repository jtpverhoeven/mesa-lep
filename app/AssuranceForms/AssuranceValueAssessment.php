<?php

namespace App\AssuranceForms;

use Carbon\CarbonImmutable;
use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;

class AssuranceValueAssessment
{
    public const TIMEZONE = 'Europe/Amsterdam';

    public function isNotApplicable(mixed $value): bool
    {
        return strtolower(trim((string) $value)) === 'nvt';
    }

    public function parseDate(mixed $value): ?CarbonImmutable
    {
        if ($value instanceof DateTimeInterface) {
            return CarbonImmutable::instance($value)->setTimezone(self::TIMEZONE)->startOfDay();
        }

        $value = trim((string) $value);

        if ($value === '') {
            return null;
        }

        foreach ([
            ['!d-m-Y', 'd-m-Y', '/^\d{2}-\d{2}-\d{4}$/'],
            ['!Y-m-d', 'Y-m-d', '/^\d{4}-\d{2}-\d{2}$/'],
            ['!d-m-Y H:i', 'd-m-Y H:i', '/^\d{2}-\d{2}-\d{4} \d{2}:\d{2}$/'],
            ['!d-m-Y H:i:s', 'd-m-Y H:i:s', '/^\d{2}-\d{2}-\d{4} \d{2}:\d{2}:\d{2}$/'],
            ['!Y-m-d H:i', 'Y-m-d H:i', '/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}$/'],
            ['!Y-m-d H:i:s', 'Y-m-d H:i:s', '/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/'],
        ] as [$format, $displayFormat, $pattern]) {
            if (preg_match($pattern, $value) !== 1) {
                continue;
            }

            $date = DateTimeImmutable::createFromFormat($format, $value, new DateTimeZone(self::TIMEZONE));
            $errors = DateTimeImmutable::getLastErrors();

            if ($date !== false
                && ($errors === false || ($errors['warning_count'] === 0 && $errors['error_count'] === 0))
                && $date->format($displayFormat) === $value) {
                return CarbonImmutable::instance($date)->startOfDay();
            }
        }

        return null;
    }

    public function isDateBeforeFormDate(mixed $value, mixed $formDate): bool
    {
        $date = $this->parseDate($value);
        $form = $this->parseDate($formDate);

        return $date !== null && $form !== null && $date->lessThan($form);
    }

    public function isUsedAfterExpiry(mixed $inoculationDate, mixed $expiryDate): bool
    {
        $inoculation = $this->parseDate($inoculationDate);
        $expiry = $this->parseDate($expiryDate);

        return $inoculation !== null && $expiry !== null && $inoculation->greaterThan($expiry);
    }

    /**
     * @param  array<string, mixed>  $media
     */
    public function isOutOfSpecification(mixed $value, mixed $formDate, array $media = []): bool
    {
        if ($value === null || trim((string) $value) === '' || $this->isNotApplicable($value)) {
            return false;
        }

        if ((int) ($media['type'] ?? 0) === 3) {
            return $this->isMaterialOutOfRange($value, $media['acceptable_range'] ?? '');
        }

        return $this->isDateBeforeFormDate($value, $formDate);
    }

    public function isMaterialOutOfRange(mixed $value, mixed $range): bool
    {
        $value = trim((string) $value);
        $range = trim((string) $range);

        if ($value === '' || $range === '' || $this->isNotApplicable($value)) {
            return false;
        }

        $numericValue = str_replace(',', '.', $value);

        if (! is_numeric($numericValue)) {
            return true;
        }

        $pattern = '/(<=|>=|<|>|=)\s*(-?\d+(?:[.,]\d+)?)/';
        $remaining = preg_replace($pattern, '', $range);

        if ($remaining === null || trim($remaining) !== '') {
            return false;
        }

        preg_match_all($pattern, $range, $matches, PREG_SET_ORDER);
        $number = (float) $numericValue;
        $acceptable = true;

        foreach ($matches as $match) {
            $bound = (float) str_replace(',', '.', $match[2]);

            $acceptable = match ($match[1]) {
                '<' => $acceptable && $number < $bound,
                '<=' => $acceptable && $number <= $bound,
                '>' => $acceptable && $number > $bound,
                '>=' => $acceptable && $number >= $bound,
                '=' => $acceptable && $number == $bound,
                default => $acceptable,
            };
        }

        return $matches !== [] && ! $acceptable;
    }

    public function valueIsMissing(mixed $value): bool
    {
        return empty($value);
    }
}
