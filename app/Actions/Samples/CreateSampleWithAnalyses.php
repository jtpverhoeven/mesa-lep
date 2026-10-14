<?php

namespace App\Actions\Samples;

use App\Actions\Printing\DispatchLabelEvents;
use App\Actions\SampleAnalyses\AddResearchProfileToSample;
use App\Actions\SampleAnalyses\AddRoamingAnalysisToSample;
use App\Models\Assay;
use App\Models\ResearchProfile;
use App\Models\Sample;
use Illuminate\Support\Facades\DB;

class CreateSampleWithAnalyses
{
    public function __construct(private DispatchLabelEvents $dispatchLabelEvents) {}

    public function handle(array $data, bool $printLabels = true): Sample
    {
        return DB::transaction(function () use ($data, $printLabels) {
            $sample = app(CreateSample::class)->handle($data, printLabels: false);

            foreach ($data['analyses'] ?? [] as $analysis) {
                if ($analysis['type'] === 'profile') {
                    app(AddResearchProfileToSample::class)->handle(
                        $sample,
                        ResearchProfile::findOrFail($analysis['profile_id']),
                        $analysis['excluded_assay_profile_ids'] ?? [],
                        printLabels: false,
                    );

                    continue;
                }

                app(AddRoamingAnalysisToSample::class)->handle(
                    $sample,
                    Assay::findOrFail($analysis['assay_id']),
                    $analysis['settings'],
                    printLabels: false,
                );
            }

            $sample = $sample->fresh(['analyses.roamingAnalysis']);

            if ($printLabels) {
                $this->dispatchLabelEvents->handle([$sample, ...$sample->analyses]);
            }

            return $sample;
        });
    }
}
