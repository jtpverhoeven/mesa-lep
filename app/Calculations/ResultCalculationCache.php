<?php

namespace App\Calculations;

use App\Calculations\Contracts\ResultCalculation;
use ReflectionClass;

class ResultCalculationCache
{
    private const META_KEY = '_calculation';

    public function isCurrent(
        mixed $storedResult,
        ResultCalculationContext $context,
        ResultCalculation $calculator,
    ): bool {
        if (! is_array($storedResult)) {
            return false;
        }

        $storedKey = $storedResult[self::META_KEY]['key'] ?? null;

        return is_string($storedKey) && hash_equals($this->key($context, $calculator), $storedKey);
    }

    public function stamp(
        array $result,
        ResultCalculationContext $context,
        ResultCalculation $calculator,
    ): array {
        $result[self::META_KEY] = [
            'calculator' => $calculator::class,
            'key' => $this->key($context, $calculator),
        ];

        return $result;
    }

    private function key(ResultCalculationContext $context, ResultCalculation $calculator): string
    {
        $calculatorFile = (new ReflectionClass($calculator))->getFileName();
        $assay = $this->sortedAttributes($context->assay->only([
            'id',
            'original_id',
            'type_base',
            'dillution',
            'replicates',
            'confirmation',
            'confirmation_type',
            'max_count',
            'min_count',
            'script',
            'custom_fields',
        ]));
        $settings = $this->sortedAttributes($context->settings->only([
            'id',
            'said',
            'research_profile',
            'assay',
            'dillutions',
            'replicates',
            'reference',
            'reference_scope',
            'reference_source',
            'conf_trip',
        ]));

        return hash('sha256', json_encode([
            'calculator' => $calculator::class,
            'implementation' => is_string($calculatorFile) ? hash_file('sha256', $calculatorFile) : null,
            'assay' => $assay,
            'settings' => $settings,
        ], JSON_THROW_ON_ERROR));
    }

    private function sortedAttributes(array $attributes): array
    {
        ksort($attributes);

        return $attributes;
    }
}
