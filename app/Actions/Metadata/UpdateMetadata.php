<?php

namespace App\Actions\Metadata;

use App\Models\Metadata;

class UpdateMetadata
{
    public function handle(Metadata $metadata, string $name, string $value): Metadata
    {
        $metadata->update([
            'name' => $name,
            'value' => $value,
        ]);

        return $metadata->fresh();
    }
}
