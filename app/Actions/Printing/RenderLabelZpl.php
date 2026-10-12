<?php

namespace App\Actions\Printing;

use DateTimeImmutable;
use DateTimeInterface;
use Exception;

class RenderLabelZpl
{
    /**
     * @param  array{sample?: array<string, mixed>, client?: array<string, mixed>, method?: array<string, mixed>, assay?: array<string, mixed>, project?: array<string, mixed>, sample_field_types?: array<string, string>}  $context
     */
    public function handle(string $template, array $context = [], ?DateTimeInterface $printedAt = null, int $dateOffset = 0, string $currentUser = ''): string
    {
        $clock = DateTimeImmutable::createFromInterface($printedAt ?? now());
        $replacements = [
            '{date_today}' => $clock->modify(sprintf('%+d days', $dateOffset))->format('d-m-Y'),
            '{yesterday}' => $clock->modify('-1 day')->format('d-m-Y'),
            '{tommorow}' => $clock->modify('+1 day')->format('d-m-Y'),
            '{current_time}' => $clock->format('H:i'),
            '{current_year}' => $clock->format('Y'),
            '{current_user}' => $currentUser,
        ];

        foreach (['sample', 'client', 'method', 'assay', 'project'] as $namespace) {
            foreach ($context[$namespace] ?? [] as $key => $value) {
                $customKey = $namespace === 'method' ? 'fields' : 'custom_fields';

                if ($key === $customKey && $namespace !== 'client') {
                    $fields = is_string($value) ? json_decode($value, true) : $value;

                    foreach (is_array($fields) ? $fields : [] as $field => $fieldValue) {
                        if ($namespace === 'sample' && ($context['sample_field_types'][$field] ?? null) === 'date' && is_int($fieldValue)) {
                            $fieldValue = $clock->setTimestamp($fieldValue)->format('d-m-Y');
                        }

                        $replacements['{'.$namespace.'_custom_'.$field.'}'] = $this->scalarValue($fieldValue);
                    }

                    continue;
                }

                if ($namespace === 'sample' && $key === 'sample_innoculated') {
                    $value = is_numeric($value) && (int) $value > 0 ? $clock->setTimestamp((int) $value)->format('d-m-Y') : '';
                } elseif ($namespace === 'sample' && $key === 'tht_date') {
                    $value = $this->thtDate($value, $clock);
                }

                $replacements['{'.$namespace.'_'.$key.'}'] = $this->scalarValue($value);
            }
        }

        return str_replace("\u{00B0}", '\\F8', strtr($template, $replacements));
    }

    private function scalarValue(mixed $value): string
    {
        return is_scalar($value) ? (string) $value : '';
    }

    private function thtDate(mixed $value, DateTimeImmutable $clock): string
    {
        if ($value instanceof DateTimeInterface) {
            return $value->format('d-m-Y');
        }

        if (! is_string($value) || $value === '') {
            return '';
        }

        try {
            return (new DateTimeImmutable($value, $clock->getTimezone()))->format('d-m-Y');
        } catch (Exception) {
            return '';
        }
    }
}
