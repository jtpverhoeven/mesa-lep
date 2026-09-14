<?php

namespace App\Casts;

use App\Confirmations\LegacyJsonObjectSerializer;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

final class LegacyJsonObjectCast implements CastsAttributes
{
    public function get(Model $model, string $key, mixed $value, array $attributes): mixed
    {
        return LegacyJsonObjectSerializer::decode($value);
    }

    public function set(Model $model, string $key, mixed $value, array $attributes): mixed
    {
        return LegacyJsonObjectSerializer::encode($value);
    }
}
