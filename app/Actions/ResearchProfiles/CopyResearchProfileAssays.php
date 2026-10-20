<?php

namespace App\Actions\ResearchProfiles;

use App\Models\ResearchProfile;
use Illuminate\Support\Facades\DB;

class CopyResearchProfileAssays
{
    public function __construct(private ValidateResearchProfileReferences $validateReferences) {}

    public function handle(ResearchProfile $source, ResearchProfile $destination): int
    {
        return DB::transaction(function () use ($source, $destination) {
            $rows = $source->assayProfiles()->where('hidden', 0)->get()->map(fn ($assay) => $assay->only([
                'assay', 'dillutions', 'replicates', 'reference', 'hidden', 'project_order', 'conf_trip', 'reference_source',
            ]));

            $this->validateReferences->handle($rows->all(), $destination->global ? null : (int) $destination->client);
            $destination->assayProfiles()->createMany($rows->all());

            return $rows->count();
        });
    }
}
