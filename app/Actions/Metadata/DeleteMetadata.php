<?php

namespace App\Actions\Metadata;

use App\ChangeTracking\ChangeTracker;
use App\Models\Metadata;
use Illuminate\Support\Facades\DB;

class DeleteMetadata
{
    public function __construct(private ChangeTracker $changeTracker) {}

    public function handle(Metadata $metadata): void
    {
        DB::transaction(function () use ($metadata): void {
            $this->changeTracker->changed(19, sample: $metadata->sample,
                event: 'Metadata verwijderd', from: $metadata->name, to: $metadata->value);
            $metadata->delete();
        });
    }
}
