<?php

namespace App\Actions\Metadata;

use App\Models\Metadata;
use App\Models\Sample;
use Illuminate\Support\Collection;

class HydrateSampleMetadata
{
    public function __construct(private CreateMetadata $createMetadata) {}

    /** @return Collection<int, Metadata> */
    public function handle(Sample $sample, array $metadata, array $portalMetadata = []): Collection
    {
        $portalKeyIds = $this->portalKeyIds($portalMetadata);
        $created = collect();

        foreach ($metadata as $name => $value) {
            if (is_array($value) && array_key_exists(0, $value) && array_key_exists(1, $value)) {
                [$name, $value] = [$value[0], $value[1]];
            }

            $name = (string) $name;
            $created->push($this->createMetadata->handle(
                $sample,
                $name,
                $this->stringValue($value),
                $portalKeyIds[$name] ?? null,
                $created->count() + 1,
            ));
        }

        return $created;
    }

    /** @return array<string, int|null> */
    private function portalKeyIds(array $portalMetadata): array
    {
        $keyIds = [];

        foreach ($portalMetadata as $name => $keyId) {
            if (is_array($keyId) && array_key_exists(0, $keyId) && array_key_exists(1, $keyId)) {
                [$name, $keyId] = [$keyId[0], $keyId[1]];
            }

            $keyIds[(string) $name] = $keyId === null || $keyId === '' ? null : (int) $keyId;
        }

        return $keyIds;
    }

    private function stringValue(mixed $value): string
    {
        if ($value === null) {
            return '';
        }

        return is_scalar($value) ? (string) $value : json_encode($value, JSON_THROW_ON_ERROR);
    }
}
