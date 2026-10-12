<?php

namespace App\Actions\LabelEvents;

use App\Models\LabelEvent;

class EvaluateLabelEvent
{
    /**
     * @param  array{event?: string, event_group?: int|string, event_data?: array<string, mixed>|string|null}  $event
     * @param  array<string, mixed>  $sample
     * @param  array<string, mixed>  $analysis
     * @param  array<string, mixed>  $assay
     */
    public function handle(array $event, array $sample = [], array $analysis = [], array $assay = []): bool
    {
        if (! is_scalar($event['event_group'] ?? null)) {
            return false;
        }

        $group = (string) $event['event_group'];

        if (! in_array($group, ['1', '2', '3'], true)) {
            return false;
        }

        if (($event['event'] ?? null) === 'P') {
            return true;
        }

        if (($event['event'] ?? null) !== 'L' || $group === '3') {
            return false;
        }

        $condition = $event['event_data'] ?? null;

        if (is_string($condition)) {
            $condition = json_decode($condition, true);
        }

        if (! is_array($condition) || ! isset($condition['type'], $condition['inspect_value'], $condition['negative'])) {
            return false;
        }

        $types = array_keys(LabelEvent::CONDITIONS[(int) $group]);

        if (! in_array($condition['type'], $types, true) || ! is_scalar($condition['negative']) || ! is_scalar($condition['inspect_value'])) {
            return false;
        }

        $negative = (string) $condition['negative'];

        if (! in_array($negative, ['0', '1'], true) || ($condition['type'] === 'sField' && (! is_string($condition['inspect'] ?? null) || $condition['inspect'] === ''))) {
            return false;
        }

        $value = $condition['inspect_value'];
        $matches = match ($condition['type']) {
            'sField' => $this->matchesSampleField($sample, $condition),
            'sClient' => array_key_exists('client', $sample) && $sample['client'] == $value,
            'mMethod' => array_key_exists('sampling_method', $sample) && $sample['sampling_method'] == $value,
            'mType' => array_key_exists('sample_type', $sample) && $sample['sample_type'] == $value,
            'assayIs' => $this->matchesAssay($analysis, (string) $value),
            'assayTypeIs' => array_key_exists('type_base', $assay) && $assay['type_base'] == $value,
        };

        return $negative === '1' ? ! $matches : $matches;
    }

    /**
     * @param  array<string, mixed>  $sample
     * @param  array<string, mixed>  $condition
     */
    private function matchesSampleField(array $sample, array $condition): bool
    {
        $fields = $sample['custom_fields'] ?? [];

        if (is_string($fields)) {
            $fields = json_decode($fields, true);
        }

        return is_array($fields)
            && is_string($condition['inspect'] ?? null)
            && array_key_exists($condition['inspect'], $fields)
            && $fields[$condition['inspect']] == $condition['inspect_value'];
    }

    /** @param array<string, mixed> $analysis */
    private function matchesAssay(array $analysis, string $value): bool
    {
        $assayIds = array_filter(array_map('trim', explode(',', $value)), fn (string $id): bool => $id !== '');

        foreach (['assay_base', 'original_assay_base'] as $key) {
            if (isset($analysis[$key]) && in_array((string) $analysis[$key], $assayIds, true)) {
                return true;
            }
        }

        return false;
    }
}
