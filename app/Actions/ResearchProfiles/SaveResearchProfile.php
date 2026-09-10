<?php

namespace App\Actions\ResearchProfiles;

use App\Models\ReferenceSource;
use App\Models\ResearchProfile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SaveResearchProfile
{
    public function handle(array $data, ?ResearchProfile $profile = null): ResearchProfile
    {
        return DB::transaction(function () use ($data, $profile) {
            if ($profile && ! $profile->active) {
                throw ValidationException::withMessages(['profile' => 'Alleen de actuele revisie kan worden aangepast.']);
            }

            $attributes = [
                'name' => $data['name'],
                'global' => (int) $data['global'],
                'client' => $data['global'] ? 0 : (int) $data['client'],
                'active' => 1,
                'portal_visible' => (int) $data['portal_visible'],
                'lims_visible' => (int) $data['lims_visible'],
            ];

            $this->validateReferenceSources($data['assays'] ?? [], $data['global'] ? null : (int) $data['client']);

            if ($profile) {
                $saved = $profile->replicate();
                $saved->fill($attributes);
                $saved->save();
                $profile->update(['active' => 0]);
            } else {
                $saved = ResearchProfile::create(['original_id' => 0, ...$attributes]);
                $saved->update(['original_id' => $saved->id]);
            }

            $saved->assayProfiles()->createMany($this->assays($data['assays'] ?? []));

            return $saved->fresh(['assayProfiles.assayRecord']);
        });
    }

    private function validateReferenceSources(array $assays, ?int $client): void
    {
        $sourceIds = collect($assays)
            ->pluck('reference_source')
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique();

        if ($sourceIds->isEmpty()) {
            return;
        }

        $availableSources = ReferenceSource::query()
            ->availableForClient($client)
            ->whereKey($sourceIds)
            ->count();

        if ($availableSources !== $sourceIds->count()) {
            throw ValidationException::withMessages([
                'assays' => 'Een geselecteerde referentiebron is niet beschikbaar voor dit profiel.',
            ]);
        }
    }

    private function assays(array $assays): array
    {
        return collect($assays)->values()->map(fn (array $assay, int $index) => [
            'assay' => $assay['assay'],
            'dillutions' => $assay['dillutions'] ?? [],
            'replicates' => $assay['replicates'],
            'reference' => $assay['reference'] ?? [],
            'hidden' => 0,
            'project_order' => $index + 1,
            'conf_trip' => $assay['conf_trip'],
            'reference_source' => $assay['reference_source'] ?? null,
        ])->all();
    }
}