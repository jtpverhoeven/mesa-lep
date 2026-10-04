<?php

namespace App\Actions\SampleAnalyses;

use App\Actions\Results\CreateAnalysisResults;
use App\Models\AssayProfile;
use App\Models\ResearchProfile;
use App\Models\Sample;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AddResearchProfileToSample
{
    public function handle(Sample $sample, ResearchProfile $profile, array $excludedAssayProfileIds = [], bool $legacyRegistration = false): Collection
    {
        return DB::transaction(function () use ($sample, $profile, $excludedAssayProfileIds, $legacyRegistration) {
            $sample = Sample::query()->findOrFail($sample->getKey());
            $profile = ResearchProfile::query()
                ->whereKey($profile->getKey())
                ->when(! $legacyRegistration, fn ($query) => $query->where('active', 1))
                ->where(function ($query) use ($sample) {
                    $query->where('global', 1)->orWhere('client', $sample->client);
                })
                ->first();

            if ($profile === null) {
                throw ValidationException::withMessages([
                    'profile' => 'Dit onderzoeksprofiel is niet actief of niet beschikbaar voor deze klant.',
                ]);
            }

            $excludedIds = collect($excludedAssayProfileIds)->map(fn ($id) => (int) $id)->all();
            $assayProfiles = $profile->assayProfiles()
                ->where('hidden', 0)
                ->when($excludedIds !== [], fn ($query) => $query->whereNotIn('id', $excludedIds))
                ->with('assayRecord')
                ->get();

            if ($legacyRegistration) {
                $assayProfiles = $assayProfiles->filter(fn (AssayProfile $assayProfile) => $assayProfile->assayRecord !== null);
            }

            $definitions = $assayProfiles->map(function (AssayProfile $assayProfile) use ($legacyRegistration) {
                if ($assayProfile->assayRecord === null) {
                    throw ValidationException::withMessages([
                        'profile' => "Analyse {$assayProfile->assay} uit het onderzoeksprofiel bestaat niet.",
                    ]);
                }

                return [
                    ...($legacyRegistration ? ['project_order' => (int) $assayProfile->project_order, 'roaming_id' => 0] : []),
                    'profile' => $assayProfile->research_profile,
                    'assay' => $assayProfile->id,
                    'assay_base' => $assayProfile->assay,
                    'original_assay_base' => $assayProfile->assayRecord->original_id,
                ];
            })->all();

            if ($legacyRegistration && $definitions === []) {
                return new Collection;
            }

            $analyses = app(CreateSampleAnalyses::class)->handle($sample, $definitions);
            $analyses->each(fn ($analysis) => app(CreateAnalysisResults::class)->handle($analysis));

            return $analyses;
        });
    }
}
