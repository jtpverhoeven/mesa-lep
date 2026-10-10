<?php

namespace App\Actions\Projects;

use App\Actions\AssuranceForms\QueueAssuranceFormSynchronization;
use App\Actions\SampleAnalyses\RemoveSampleAnalysis;
use App\ChangeTracking\ChangeTracker;
use App\Models\Project;
use App\Models\Result;
use App\Models\Sample;
use Illuminate\Support\Facades\DB;

class DeleteProjectSearchSample
{
    public function __construct(
        private RemoveSampleAnalysis $removeAnalysis,
        private QueueAssuranceFormSynchronization $queueAssuranceSync,
        private ChangeTracker $changeTracker,
    ) {}

    public function handle(Project $project, Sample $sample): void
    {
        DB::transaction(function () use ($project, $sample): void {
            $lockedProject = Project::query()->lockForUpdate()->findOrFail($project->id);
            $lockedSample = Sample::query()->lockForUpdate()->findOrFail($sample->id);

            abort_unless((int) $lockedSample->project === (int) $lockedProject->id, 404);
            abort_if($lockedProject->auth_status || $lockedProject->locked, 403, 'Dit project is vergrendeld of geautoriseerd.');

            foreach ($lockedSample->analyses()->get() as $analysis) {
                $analysis->confirmationRecord()->delete();
                $this->removeAnalysis->handle($analysis);
            }

            Result::query()->where('sample', $lockedSample->id)->delete();
            $lockedSample->metadata()->delete();
            $this->changeTracker->changed(11, project: $lockedProject->id, sample: $lockedSample->id,
                event: 'Monster verwijderd: '.$lockedSample->barcode, from: false, to: false);
            $lockedSample->delete();
            $lockedProject->update(['last_edit' => now()->timestamp]);
            $this->queueAssuranceSync->handle($lockedSample->sample_innoculated);
        });
    }
}
