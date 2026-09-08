<?php

namespace App\Actions\Media;

use App\Models\Media;

final class MediaAttributes
{
    public function from(array $data, ?Media $existing = null): array
    {
        $type = (int) ($data['type'] ?? $existing?->type ?? 1);
        $confirmationMedia = (int) ($data['confirmation_media'] ?? $existing?->confirmation_media ?? 0);

        return [
            'name' => $data['name'] ?? $existing?->name,
            'short_name' => array_key_exists('short_name', $data)
                ? $data['short_name']
                : $existing?->short_name,
            'confirmation_media' => $confirmationMedia,
            'type' => $type,
            'supplements' => $this->supplements($data['supplements_txt'] ?? '', $type),
            'hasDate' => (int) ($data['hasDate'] ?? $existing?->hasDate ?? 1),
            'confirmation_controls' => json_encode(
                $this->confirmationControls($data, $confirmationMedia),
                JSON_THROW_ON_ERROR,
            ),
            'used_for_prediction' => $this->booleanValue(
                $data,
                'used_for_prediction',
                (int) ($existing?->used_for_prediction ?? 1),
            ),
            'prediction_default_quant' => (int) ($data['prediction_default_quant']
                ?? $existing?->prediction_default_quant
                ?? 18),
            'acceptable_range' => $type === 3
                ? (($value = trim((string) ($data['acceptable_range'] ?? $existing?->acceptable_range ?? ''))) === '' ? null : $value)
                : null,
        ];
    }

    private function supplements(string $value, int $type): string
    {
        $value = trim($value);

        if ($type === 3) {
            return $value;
        }

        $supplements = [];
        foreach (preg_split('/\R/', $value) ?: [] as $line) {
            $line = trim($line);

            if ($line === '') {
                continue;
            }

            $supplements[] = [
                'supplementId' => count($supplements),
                'name' => $line,
            ];
        }

        return json_encode($supplements, JSON_THROW_ON_ERROR);
    }

    private function confirmationControls(array $data, int $confirmationMedia): array
    {
        if ($confirmationMedia !== 1) {
            return [];
        }

        $controls = [];
        foreach (['pos', 'neg', 'blank'] as $control) {
            if ($this->booleanValue($data, 'conf_enabled_'.$control, 0) === 1) {
                $controls[$control] = true;
            }
        }

        return $controls;
    }

    private function booleanValue(array $data, string $key, int $default): int
    {
        if (! array_key_exists($key, $data)) {
            return $default;
        }

        return filter_var($data[$key], FILTER_VALIDATE_BOOLEAN) ? 1 : 0;
    }
}   