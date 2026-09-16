<?php

namespace App\Actions\Confirmations;

use App\Actions\AssuranceForms\QueueAssuranceFormSynchronization;
use App\Jobs\CalculateAnalysisResultJob;
use App\Models\Confirmation;
use App\Models\Project;
use App\Models\SampleAnalysis;
use Closure;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ConfirmationMutation
{
    public function __construct(private QueueAssuranceFormSynchronization $queueAssuranceSync) {}

    public function execute(SampleAnalysis $analysis, Closure $callback): SampleAnalysis
    {
        $updated = DB::transaction(function () use ($analysis, $callback): SampleAnalysis {
            $locked = SampleAnalysis::query()
                ->with(['assayRecord', 'results'])
                ->lockForUpdate()
                ->findOrFail($analysis->getKey());

            $project = $locked->project === null
                ? null
                : Project::query()->lockForUpdate()->findOrFail($locked->project);

            if ($project?->locked || $project?->auth_status) {
                throw ValidationException::withMessages([
                    'confirmation' => 'Dit project is vergrendeld of geautoriseerd.',
                ]);
            }

            $confirmation = Confirmation::query()
                ->where('said', $locked->id)
                ->lockForUpdate()
                ->first();

            return $callback($locked, $confirmation);
        });

        $updated->loadMissing('sampleRecord');

        if ($updated->sampleRecord?->sample_innoculated !== null && $updated->sampleRecord?->sample_innoculated !== '') {
            $this->queueAssuranceSync->handle($updated->sampleRecord->sample_innoculated);
        }

        CalculateAnalysisResultJob::dispatch($updated->id)->afterCommit();

        return $updated->fresh();
    }

    public function requireConfirmation(?Confirmation $confirmation): Confirmation
    {
        if ($confirmation === null) {
            throw ValidationException::withMessages([
                'confirmation' => 'Bevestiging is nog niet ingeschakeld.',
            ]);
        }

        return $confirmation;
    }

    public function assertScope(SampleAnalysis $analysis, string $df, int $rep): void
    {
        if ((int) $analysis->assayRecord?->confirmation_type === 0) {
            if ($df !== 'global' || $rep !== 0) {
                throw ValidationException::withMessages(['df' => 'Deze analyse gebruikt een globale bevestiging.']);
            }

            return;
        }

        $exists = $analysis->results->contains(
            fn ($result): bool => (string) $result->df === $df && (int) $result->rep === $rep,
        );

        if (! $exists) {
            throw ValidationException::withMessages(['df' => 'Deze verdunning en replica horen niet bij de analyse.']);
        }
    }

    public function invalidate(SampleAnalysis $analysis): void
    {
        $analysis->is_ready = false;
        $analysis->storedResult = null;
        $analysis->save();
    }
}
