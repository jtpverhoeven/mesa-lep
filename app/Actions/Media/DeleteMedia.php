<?php

namespace App\Actions\Media;

use App\Models\Media;
use Illuminate\Support\Facades\DB;

class DeleteMedia
{
    public function handle(Media $media): Media
    {
        return DB::transaction(function () use ($media): Media {
            $media->update(['active' => 0]);

            return $media->fresh();
        });
    }
}