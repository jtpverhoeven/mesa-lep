<?php

namespace App\Confirmations;

use App\Calculations\ResultCalculationContext;

final readonly class ConfirmationTrigger
{
    public function __construct(
        public bool $eligible,
        public ?float $numericValue,
        public ?string $disposition,
        public int $targetAnalysisId,
    ) {}

    public static function fromCalculation(array $calculation, ResultCalculationContext $context): self
    {
        $structured = $calculation['confirmationTrigger'] ?? null;

        if (is_array($structured)) {
            return new self(
                (bool) ($structured['eligible'] ?? false),
                is_numeric($structured['numericValue'] ?? null) ? (float) $structured['numericValue'] : null,
                isset($structured['disposition']) ? (string) $structured['disposition'] : null,
                (int) ($structured['targetAnalysisId'] ?? $context->analysis->id),
            );
        }

        $reportField = (string) ($calculation['reportIn'] ?? 'result');
        $value = $calculation['output'][$reportField] ?? $calculation['output']['kve'] ?? null;
        $disposition = $calculation['disposition'][$reportField]
            ?? $calculation['disposition']['kve']
            ?? null;
        $numericValue = self::numericValue($value);
        $eligible = (bool) ($calculation['isReady'] ?? false)
            && $numericValue !== null
            && $numericValue > 0
            && ! self::isBoundedValue($value)
            && $disposition !== '-'
            && $disposition !== '<';

        return new self($eligible, $numericValue, $disposition, (int) $context->analysis->id);
    }

    private static function numericValue(mixed $value): ?float
    {
        if (is_numeric($value)) {
            return (float) $value;
        }

        if (! is_string($value) || preg_match('/-?\d+(?:[.,]\d+)?/', strip_tags($value), $matches) !== 1) {
            return null;
        }

        return (float) str_replace(',', '.', $matches[0]);
    }

    private static function isBoundedValue(mixed $value): bool
    {
        return is_string($value) && preg_match('/^\s*[<>]/', strip_tags($value)) === 1;
    }
}
