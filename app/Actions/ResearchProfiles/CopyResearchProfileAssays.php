<?php

namespace App\Actions\ResearchProfiles;

use App\Models\ResearchProfile;
use Illuminate\Support\Facades\DB;

class CopyResearchProfileAssays
{
    public function handle(ResearchProfile $source, ResearchProfile $destination): int
    {
        return DB::transaction(function () use ($source, $destination) {
            $rows = $source->assayProfiles()->where('hidden', 0)->get()->map(function ($assay) use ($destination) {
                $copy = $assay->replicate();
                $copy->research_profile = $destination->id;

                return $copy->getAttributes();
            });

            $destination->assayProfiles()->createMany($rows->all());

            return $rows->count();
        });
    }
}