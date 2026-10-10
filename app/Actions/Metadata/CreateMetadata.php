<?php

namespace App\Actions\Metadata;

use App\ChangeTracking\ChangeTracker;
use App\Models\Metadata;
use App\Models\Sample;
use Illuminate\Support\Facades\DB;

class CreateMetadata
{
    public function __construct(private ChangeTracker $changeTracker) {}

    public function handle(
        Sample $sample,
        string $name,
        string $value,
        ?int $metaDataKeyId = null,
        ?int $order = null,
    ): Metadata {
        return DB::transaction(function () use ($sample, $name, $value, $metaDataKeyId, $order): Metadata {
            $metadata = $sample->metadata()->create([
                'name' => $name,
                'value' => $value,
                'meta_data_key_id' => $metaDataKeyId,
                'meta_order' => $order ?? $sample->metadata()->count() + 1,
            ]);
            $this->changeTracker->changed(18, project: $sample->project, sample: $sample->id,
                event: 'Metadata toegevoegd', from: $name, to: $value, userId: auth()->id() ?? (int) $sample->registered_by);

            return $metadata;
        });
    }
}
