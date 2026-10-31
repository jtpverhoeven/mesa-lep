<?php

namespace App\Actions\Samples;

use App\Actions\AssuranceForms\GetOrCreateAssuranceForm;
use App\Actions\AssuranceForms\QueueAssuranceFormSynchronization;
use App\Actions\AssuranceForms\ResolveAssuranceDay;
use App\Actions\Projects\EnsureEditable;
use App\ChangeTracking\ChangeTracker;
use App\Models\Project;
use App\Models\Sample;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class UpdateSampleInoculationConditions
{
    public function __construct(
        private ResolveAssuranceDay $resolveDay,
        private GetOrCreateAssuranceForm $getOrCreateForm,
        private QueueAssuranceFormSynchronization $queueAssuranceSync,
        private EstimateRegisteredSampleEndpoints $estimate,
        private ChangeTracker $changeTracker,
        private EnsureEditable $ensureEditable,
    ) {}

    public function handle(Sample $sample, CarbonInterface $inoculatedAt, string $storage, string $dilutedAt): Sample
    {
        $previousInoculation = '';
        $updated = DB::transaction(function () use ($sample, $inoculatedAt, $storage, $dilutedAt, &$previousInoculation): Sample {
            $lockedSample = Sample::query()->lockForUpdate()->findOrFail($sample->getKey());
            $project = (int) $lockedSample->project > 0
                ? Project::query()->lockForUpdate()->find($lockedSample->project)
                : null;

            $this->ensureEditable->handle($project);
            $previousInoculation = (string) ($lockedSample->sample_innoculated ?? '');
            if (trim($previousInoculation) === '' || trim($previousInoculation) === '0') {
                throw ValidationException::withMessages(['sample' => 'Kan niet wijzigen. Dit monster is nog niet gescand.']);
            }

            $lockedSample->sample_innoculated = (string) $inoculatedAt->timestamp;
            $lockedSample->stored_in = $storage;
            $lockedSample->diluted_at = $dilutedAt;
            $lockedSample->save();
            if ($project !== null) {
                $this->estimate->handle($lockedSample);
            }
            $this->changeTracker->changed(4, project: $lockedSample->project, sample: $lockedSample->id,
                event: 'Inzet datum / tijd, opslag bak en afweegstation  manueel gewijzigd naar: '
                    .$inoculatedAt->format('d-m-Y/H:i').' Opslag:'.$storage.' afweegstation : '.$dilutedAt,
                from: false, to: false);

            return $lockedSample->fresh();
        });

        $newFormDate = $this->resolveDay->handle($updated->sample_innoculated)['form_date'];
        $this->getOrCreateForm->handle($newFormDate);
        $this->queueAssuranceSync->handleMany([$previousInoculation, $updated->sample_innoculated]);

        return $updated;
    }
}
