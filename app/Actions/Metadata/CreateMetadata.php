<?php

namespace App\Actions\Metadata;

use App\Models\Metadata;
use App\Models\Sample;

class CreateMetadata
{
    public function handle(
        Sample $sample,
        string $name,
        string $value,
        ?int $metaDataKeyId = null,
        ?int $order = null,
    ): Metadata {
        return $sample->metadata()->create([
            'name' => $name,
            'value' => $value,
            'meta_data_key_id' => $metaDataKeyId,
            'meta_order' => $order ?? $sample->metadata()->count() + 1,
        ]);
    }
}
