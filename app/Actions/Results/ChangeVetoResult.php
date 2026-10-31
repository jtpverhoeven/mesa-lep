<?php

namespace App\Actions\Results;

use App\Actions\Projects\EnsureEditable;
use App\Calculations\Exceptions\ResultCalculationException;
use App\ChangeTracking\ChangeTracker;
use App\Jobs\CalculateAnalysisResultJob;
use App\Keyrings\KeyringType;
use App\Models\Project;
use App\Models\Sample;
use App\Models\SampleAnalysis;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ChangeVetoResult
{
    public function __construct(
        private CalculateAnalysisResult $calculate,
        private ChangeTracker $changeTracker,
        private EnsureEditable $ensureEditable,
    ) {}

    public function handle(
        Project $project,
        Sample $sample,
        SampleAnalysis $analysis,
        string $parameter,
        ?string $value,
        string $reason,
        ?string $disposition = null,
    ): void {
        try {
            DB::transaction(function () use ($project, $sample, $analysis, $parameter, $value, $reason, $disposition): void {
                $analysis = SampleAnalysis::query()->with(['assayRecord', 'sampleRecord'])->lockForUpdate()->findOrFail($analysis->id);
                $project = Project::query()->lockForUpdate()->findOrFail($project->id);

                abort_unless(
                    (int) $analysis->sample === (int) $sample->id
                        && (int) $analysis->project === (int) $project->id
                        && (int) $analysis->sampleRecord?->project === (int) $project->id,
                    404,
                );
                $this->ensureEditable->handle($project, KeyringType::Sample, (int) $sample->id);

                if ($value !== null && in_array((int) $analysis->assayRecord?->type, [2, 4], true) && $disposition === null) {
                    throw ValidationException::withMessages(['disposition' => 'Selecteer een uitslagtype voor deze analyse.']);
                }

                $before = $this->calculate->handle($analysis);
                $veto = $analysis->vetoResults()->where('parameter', $parameter)->first();

                if ($value === null) {
                    abort_if($veto === null, 404);
                    $analysis->vetoResults()->where('parameter', $parameter)->delete();
                } else {
                    if (! array_key_exists($parameter, $before['output'] ?? [])) {
                        throw ValidationException::withMessages(['parameter' => 'Dit resultaatveld hoort niet bij deze berekening.']);
                    }

                    $analysis->vetoResults()->updateOrCreate(
                        ['parameter' => $parameter],
                        ['result' => $value, 'reason' => $reason, 'disposition' => $disposition],
                    );
                }

                $analysis->update(['storedResult' => null]);
                $after = $this->calculate->handle($analysis);
                $event = $value === null ? 'Veto resultaat verwijderd' : 'Veto resultaat aangepast';
                $this->changeTracker->changed(
                    11,
                    project: $project->id,
                    sample: $sample->id,
                    said: $analysis->id,
                    event: $event.' voor monster '.$analysis->sampleRecord->barcode.' in analyse '.$analysis->assayRecord->name.'. Reden: '.$reason,
                    from: $parameter.':'.($before['output'][$parameter] ?? $veto?->result ?? ''),
                    to: $parameter.':'.($after['output'][$parameter] ?? ''),
                );
                $project->update(['last_edit' => now()->timestamp]);
                // Unique-job locks must also be acquired outside the PostgreSQL transaction.
                DB::afterCommit(function () use ($analysis): void {
                    CalculateAnalysisResultJob::dispatch($analysis->id);
                });
            });
        } catch (ResultCalculationException $exception) {
            report($exception);

            throw ValidationException::withMessages(['result' => $exception->userMessage()]);
        }
    }
}
