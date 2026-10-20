<?php

namespace App\Actions\Imports;

use App\Models\AssayProfile;
use Illuminate\Database\Eloquent\Model;
use JsonException;
use RuntimeException;

class DecodeCsvJsonAttributes
{
    /**
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    public function handle(Model $model, array $attributes): array
    {
        if ($model instanceof AssayProfile) {
            foreach (['dillutions', 'reference'] as $attribute) {
                if (array_key_exists($attribute, $attributes)) {
                    $attributes[$attribute] = $this->decodeLegacyArray($attribute, $attributes[$attribute]);
                }
            }
        }

        foreach ($model->getCasts() as $attribute => $cast) {
            if (! in_array($cast, ['array', 'json'], true) || ! isset($attributes[$attribute]) || ! is_string($attributes[$attribute])) {
                continue;
            }

            $attributes[$attribute] = $this->decodeJson($attribute, $attributes[$attribute]);
        }

        return $attributes;
    }

    /**
     * @return array<array-key, mixed>
     */
    private function decodeLegacyArray(string $attribute, mixed $value): array
    {
        for ($depth = 0; $depth < 2 && is_string($value); $depth++) {
            if (in_array($value, ['', 'NA', 'NULL'], true)) {
                return [];
            }

            $value = $this->decodeJson($attribute, $value);
        }

        if ($value === null || $value === '') {
            return [];
        }

        if (! is_array($value)) {
            throw new RuntimeException("{$attribute} must contain a JSON object or array.");
        }

        return $value;
    }

    private function decodeJson(string $attribute, string $value): mixed
    {
        try {
            return json_decode($value, true, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new RuntimeException("{$attribute} contains invalid JSON: {$exception->getMessage()}", previous: $exception);
        }
    }
}
