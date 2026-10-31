<?php

namespace App\Actions\Samples;

use App\Actions\AssuranceForms\QueueAssuranceFormSynchronization;
use App\Actions\Printing\DispatchLabelEvents;
use App\Actions\Projects\EnsureEditable;
use App\Actions\SampleAnalyses\AddResearchProfileToSample;
use App\Actions\SampleAnalyses\AddRoamingAnalysisToSample;
use App\Actions\SampleAnalyses\RemoveSampleAnalysis;
use App\Actions\SampleAnalyses\ReorderSampleAnalyses;
use App\Keyrings\KeyringType;
use App\Models\Assay;
use App\Models\Project;
use App\Models\ResearchProfile;
use App\Models\Sample;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class UpdateLookupResearch
{
    public function __construct(private QueueAssuranceFormSynchronization $queueAssuranceSync, private DispatchLabelEvents $dispatchLabelEvents, private EnsureEditable $ensureEditable) {}

    public function handle(Sample $sample, array $data, bool $printLabels = true): Collection
    {
        $formDateSource = $sample->sample_innoculated;

        $analyses = DB::transaction(function () use ($sample, $data, $printLabels): Collection {
            $analyses = new Collection;
            $sample = Sample::query()->lockForUpdate()->findOrFail($sample->id);
            $project = Project::query()->lockForUpdate()->findOrFail($sample->project);
            $this->ensureEditable->handle($project, KeyringType::Sample, $sample->id);

            if ($data['operation'] === 'remove') {
                $analysis = $sample->analyses()->findOrFail($data['analysis_id']);
                if ($analysis->is_ready || $analysis->storedResult !== null || $analysis->conf_requested) {
                    throw ValidationException::withMessages(['analysis' => 'Onderzoek met resultaten of bevestigingen kan nog niet worden verwijderd.']);
                }
                app(RemoveSampleAnalysis::class)->handle($analysis);
            } elseif ($data['operation'] === 'reorder') {
                app(ReorderSampleAnalyses::class)->handle($sample, $data['analysis_ids']);
            } else {
                foreach ($data['analyses'] as $entry) {
                    if ($entry['type'] === 'profile') {
                        $analyses = $analyses->merge(app(AddResearchProfileToSample::class)->handle($sample, ResearchProfile::findOrFail($entry['profile_id']), $entry['excluded_assay_profile_ids'] ?? [], printLabels: false));
                    } else {
                        $analyses->add(app(AddRoamingAnalysisToSample::class)->handle($sample, Assay::findOrFail($entry['assay_id']), $entry['settings'], printLabels: false));
                    }
                }
            }

            if ($printLabels) {
                $this->dispatchLabelEvents->handle($analyses);
            }

            return $analyses;
        }, 3);

        if ($formDateSource !== null && $formDateSource !== '') {
            $this->queueAssuranceSync->handle($formDateSource);
        }

        return $analyses;
    }
}
