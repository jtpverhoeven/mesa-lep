<?php

namespace App\Actions\ReferenceSources;

use App\Models\AssayProfile;
use App\Models\ReferenceSource;
use Illuminate\Support\Facades\DB;

class DeleteReferenceSource
{
    public function handle(ReferenceSource $referenceSource): ReferenceSource
    {
        return DB::transaction(function () use ($referenceSource): ReferenceSource {
            AssayProfile::query()
                ->where('reference_source', $referenceSource->id)
                ->update(['reference_source' => null]);

            $referenceSource->delete();

            return $referenceSource;
        });
    }
}