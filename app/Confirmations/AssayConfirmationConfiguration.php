<?php

namespace App\Confirmations;

use JsonException;
use RuntimeException;

final class AssayConfirmationConfiguration
{
    public static function decode(mixed $value): array
    {
        if (is_array($value)) {
            return array_values($value);
        }

        if ($value === null || $value === '') {
            return [];
        }

        if (! is_string($value)) {
            return [];
        }

        try {
            $decoded = json_decode($value, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return [];
        }

        return is_array($decoded) ? array_values($decoded) : [];
    }

    public static function serializeScript(mixed $value): string
    {
        if ($value === null) {
            return '[]';
        }

        if (! is_array($value)) {
            return (string) $value;
        }

        return json_encode(self::normalizeScript($value), JSON_THROW_ON_ERROR);
    }

    public static function serializeSupport(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        if (! is_array($value)) {
            return (string) $value;
        }

        return json_encode(self::normalizeSupport($value), JSON_THROW_ON_ERROR);
    }

    public static function normalizeScript(array $script): array
    {
        return self::normalizeChain($script, true);
    }

    public static function normalizeSupport(array $support): array
    {
        return self::normalizeChain($support, false);
    }

    private static function normalizeChain(array $items, bool $withDisposition): array
    {
        $normalized = [];

        foreach (array_values($items) as $index => $item) {
            if (! is_array($item) || ! array_key_exists('mediaId', $item)) {
                throw new RuntimeException('Confirmation media rows must contain a mediaId.');
            }

            $row = [
                'mediaId' => (int) $item['mediaId'],
                'chainId' => $index + 1,
            ];

            if ($withDisposition) {
                $row['disposition'] = (string) ($item['disposition'] ?? '');
            }

            $normalized[] = $row;
        }

        return $normalized;
    }
}
