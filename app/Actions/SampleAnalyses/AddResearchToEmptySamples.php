<?php

namespace App\Actions\SampleAnalyses;

use App\Actions\Samples\UpdateLookupResearch;
use App\Models\Sample;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AddResearchToEmptySamples
{
    public function __construct(
        private RefreshSampleIsEmpty $refreshSampleIsEmpty,
        private UpdateLookupResearch $updateLookupResearch,
    ) {}

    /**
     * @param  list<int>  $sampleIds
     * @param list<array{
     *     type: 'profile'|'assay',
     *     profile_id?: int,
     *     excluded_assay_profile_ids?: list<int>,
     *     assay_id?: int,
     *     settings?: array<string, mixed>
     * }> $analyses
     */
    public function handle(array $sampleIds, array $analyses): int
    {
        $samplesAreEmpty = DB::transaction(function () use ($sampleIds, $analyses): bool {
            $samples = Sample::query()
                ->whereKey($sampleIds)
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            if ($samples->count() !== count($sampleIds)) {
                throw ValidationException::withMessages([
                    'sample_ids' => 'Een of meer geselecteerde monsters bestaan niet meer.',
                ]);
            }

            $clientIds = $samples->pluck('client')->unique();

            if ($clientIds->count() !== 1 || $clientIds->first() === null) {
                throw ValidationException::withMessages([
                    'sample_ids' => 'Selecteer monsters van dezelfde klant.',
                ]);
            }

            foreach ($samples as $sample) {
                if (! $this->refreshSampleIsEmpty->handle($sample)) {
                    return false;
                }
            }

            foreach ($samples as $sample) {
                $this->updateLookupResearch->handle($sample, [
                    'operation' => 'add',
                    'analyses' => $analyses,
                ]);
            }

            return true;
        }, 3);

        if (! $samplesAreEmpty) {
            throw ValidationException::withMessages([
                'sample_ids' => 'Een of meer geselecteerde monsters hebben inmiddels analyses. Vernieuw de lijst.',
            ]);
        }

        return count($sampleIds);
    }
}
