<?php

namespace App\Actions\Metadata;

use App\ChangeTracking\ChangeTracker;
use App\Models\Metadata;
use Illuminate\Support\Facades\DB;

class UpdateMetadata
{
    public function __construct(private ChangeTracker $changeTracker) {}

    public function handle(Metadata $metadata, string $name, string $value): Metadata
    {
        return DB::transaction(function () use ($metadata, $name, $value): Metadata {
            $previous = $metadata->value;
            $previousName = $metadata->name;
            $metadata->update([
                'name' => $name,
                'value' => $value,
            ]);
            $this->changeTracker->changed(18, sample: $metadata->sample,
                event: 'Metadata gewijzigd: '.$previousName.' -> '.$name, from: $previous, to: $value);

            return $metadata->fresh();
        });
    }
}
