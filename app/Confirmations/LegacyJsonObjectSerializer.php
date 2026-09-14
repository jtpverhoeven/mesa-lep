<?php

namespace App\Confirmations;

use JsonSerializable;
use Traversable;
use UnexpectedValueException;

final class LegacyJsonObjectSerializer
{
    public static function decode(mixed $value): ?array
    {
        if ($value === null) {
            return null;
        }

        if ($value === '') {
            return [];
        }

        if (is_string($value)) {
            $value = json_decode($value, true, 512, JSON_THROW_ON_ERROR);
        }

        $value = self::normalize($value);

        if (! is_array($value)) {
            throw new UnexpectedValueException(
                'Legacy confirmation JSON must decode to an array or object.'
            );
        }

        return $value;
    }

    public static function encode(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        if (is_string($value)) {
            $value = self::decode($value);
        }

        $value = self::normalize($value);

        if (! is_array($value)) {
            throw new UnexpectedValueException(
                'Legacy confirmation JSON must be assigned from an array or object.'
            );
        }

        return json_encode($value, JSON_FORCE_OBJECT | JSON_THROW_ON_ERROR);
    }

    private static function normalize(mixed $value): mixed
    {
        if ($value instanceof JsonSerializable) {
            return self::normalize($value->jsonSerialize());
        }

        if ($value instanceof Traversable) {
            return self::normalize(iterator_to_array($value));
        }

        if (is_object($value)) {
            return self::normalize(get_object_vars($value));
        }

        return $value;
    }
}
