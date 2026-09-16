<?php

namespace App\Actions\AssuranceForms;

use App\AssuranceForms\AssuranceValueAssessment;
use Carbon\CarbonImmutable;
use DateTimeInterface;
use InvalidArgumentException;

class ResolveAssuranceDay
{
    public function __construct(private AssuranceValueAssessment $assessment) {}

    /**
     * @return array{form_date: string, timestamp: string, display_date: string}
     */
    public function handle(mixed $value): array
    {
        $date = $this->date($value);

        if ($date === null) {
            throw new InvalidArgumentException('Ongeldige inzetdatum.');
        }

        return [
            'form_date' => $date->format('Y-m-d'),
            'timestamp' => (string) $date->timestamp,
            'display_date' => $date->format('d-m-Y'),
        ];
    }

    private function date(mixed $value): ?CarbonImmutable
    {
        if ($value instanceof DateTimeInterface) {
            return CarbonImmutable::instance($value)
                ->setTimezone(AssuranceValueAssessment::TIMEZONE)
                ->startOfDay();
        }

        if (is_int($value) || (is_string($value) && preg_match('/^-?\d+$/', trim($value)) === 1)) {
            return CarbonImmutable::createFromTimestamp((int) $value, 'UTC')
                ->setTimezone(AssuranceValueAssessment::TIMEZONE)
                ->startOfDay();
        }

        $value = trim((string) $value);

        if ($value === '') {
            return null;
        }

        return $this->assessment->parseDate($value);
    }
}
