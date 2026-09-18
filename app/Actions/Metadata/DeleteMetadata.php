<?php

namespace App\Actions\Metadata;

use App\Models\Metadata;

class DeleteMetadata
{
    public function handle(Metadata $metadata): void
    {
        $metadata->delete();
    }
}
