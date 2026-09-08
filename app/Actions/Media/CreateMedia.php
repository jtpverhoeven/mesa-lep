<?php

namespace App\Actions\Media;

use App\Models\Media;
use App\Actions\Media\MediaAttributes;
use Illuminate\Support\Facades\DB;

class CreateMedia
{
    public function __construct(private readonly MediaAttributes $attributes)
    {
    }

    public function handle(array $data): Media
    {
        return DB::transaction(fn (): Media => Media::create($this->attributes->from($data)));
    }
}