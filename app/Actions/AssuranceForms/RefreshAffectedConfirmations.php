<?php

namespace App\Actions\AssuranceForms;

use App\Actions\Confirmations\RecalculateConfirmation;
use App\Events\ConfirmationUpdated;
use App\Jobs\CalculateAnalysisResultJob;
use App\Models\AssuranceForm;
use App\Models\SampleAnalysis;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

class RefreshAffectedConfirmations
{
    public function __construct(private RecalculateConfirmation $recalculate) {}

    public function handle(AssuranceForm $form, ?int $mediaId = null): void
    {
        $start = CarbonImmutable::createFromTimestamp((int) $form->date, 'Europe/Amsterdam');
        $end = $start->addDay();
        $analyses = SampleAnalysis::query()
            ->where('conf_requested', 1)
            ->whereHas('sampleRecord', fn ($query) => $query
                ->where('sample_innoculated', '>=', (string) $start->timestamp)
                ->where('sample_innoculated', '<', (string) $end->timestamp))
            ->with(['assayRecord', 'results', 'confirmationRecord'])
            ->get();

        foreach ($analyses as $analysis) {
            $confirmation = $analysis->confirmationRecord;

            if ($confirmation === null || ($mediaId !== null && ! $this->usesMedia($confirmation->in_use, $mediaId))) {
                continue;
            }

            $evaluation = DB::transaction(function () use ($analysis): array {
                $locked = SampleAnalysis::query()
                    ->with(['assayRecord', 'results', 'confirmationRecord'])
                    ->lockForUpdate()
                    ->find($analysis->id);

                if ($locked === null || $locked->confirmationRecord === null) {
                    return [];
                }

                $result = $this->recalculate->handle($locked, $locked->confirmationRecord, true);
                $locked->is_ready = false;
                $locked->storedResult = null;
                $locked->save();

                return $result;
            });

            if ($evaluation !== []) {
                $sampleId = (int) $analysis->sample;
                $analysisId = (int) $analysis->id;
                $stateHash = hash('sha256', json_encode($evaluation, JSON_THROW_ON_ERROR));

                DB::afterCommit(function () use ($sampleId, $analysisId, $stateHash): void {
                    broadcast(new ConfirmationUpdated($sampleId, $analysisId, $stateHash))->toOthers();
                    CalculateAnalysisResultJob::dispatch($analysisId);
                });
            }
        }
    }

    private function usesMedia(mixed $inUse, int $mediaId): bool
    {
        if (! is_array($inUse)) {
            return false;
        }

        foreach ($inUse as $scope) {
            if (! is_array($scope)) {
                continue;
            }

            foreach ($scope as $mediaValues) {
                if (is_array($mediaValues) && filter_var($mediaValues[$mediaId] ?? $mediaValues[(string) $mediaId] ?? false, FILTER_VALIDATE_BOOLEAN)) {
                    return true;
                }
            }
        }

        return false;
    }
}
