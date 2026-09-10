<?php

namespace App\Actions\Samples;

use App\Actions\SampleAnalyses\AddResearchProfileToSample;
use App\Actions\SampleAnalyses\AddRoamingAnalysisToSample;
use App\Models\Assay;
use App\Models\ResearchProfile;
use App\Models\Sample;
use Illuminate\Support\Facades\DB;

class CreateSampleWithAnalyses
{
    public function handle(array $data): Sample
    {
        return DB::transaction(function () use ($data) {
            $sample = app(CreateSample::class)->handle($data);

            foreach ($data['analyses'] ?? [] as $analysis) {
                if ($analysis['type'] === 'profile') {
                    app(AddResearchProfileToSample::class)->handle(
                        $sample,
                        ResearchProfile::findOrFail($analysis['profile_id']),
                        $analysis['excluded_assay_profile_ids'] ?? [],
                    );

                    continue;
                }

                app(AddRoamingAnalysisToSample::class)->handle(
                    $sample,
                    Assay::findOrFail($analysis['assay_id']),
                    $analysis['settings'],
                );
            }

            return $sample->fresh(['analyses.roamingAnalysis']);
        });
    }
}
