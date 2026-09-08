<?php

namespace App\Actions\Media;

use App\Models\Media;
use App\Actions\Media\MediaAttributes;
use Illuminate\Support\Facades\DB;

class UpdateMedia
{
    public function __construct(private readonly MediaAttributes $attributes)
    {
    }

    public function handle(Media $media, array $data): Media
    {
        return DB::transaction(function () use ($media, $data): Media {
            $media->fill($this->attributes->from($data, $media));
            $media->save();

            return $media->fresh();
        });
    }
}