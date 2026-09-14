<?php

namespace App\Confirmations;

use App\Models\Assay;
use App\Models\Confirmation;
use App\Models\SampleAnalysis;

class SynchronizeConfirmationScopes
{
    public function __construct(private DetermineConfirmationContenderCount $contenderCount) {}

    /** @return list<array{df: string, rep: int, applicable: bool, count: int}> */
    public function scopes(SampleAnalysis $analysis, Assay $assay): array
    {
        $analysis->loadMissing('results');

        if ((int) $assay->confirmation_type === 0) {
            $info = $this->contenderCount->handle($analysis, 'global', 0, (int) $assay->confirmation_depth);

            return [['df' => 'global', 'rep' => 0, ...$info]];
        }

        return $analysis->results
            ->map(fn ($result): array => [
                'df' => (string) $result->df,
                'rep' => (int) $result->rep,
            ])
            ->unique(fn (array $scope): string => $scope['df'].':'.$scope['rep'])
            ->map(fn (array $scope): array => [
                ...$scope,
                ...$this->contenderCount->handle(
                    $analysis,
                    $scope['df'],
                    $scope['rep'],
                    (int) $assay->confirmation_depth,
                ),
            ])
            ->values()
            ->all();
    }

    public function handle(SampleAnalysis $analysis, Confirmation $confirmation): void
    {
        $assay = $analysis->assayRecord;

        if (! $assay) {
            return;
        }

        if ($confirmation->exists && (int) $assay->confirmation_type === 0) {
            return;
        }

        $steps = AssayConfirmationConfiguration::decode($assay->confirmation_script);
        $metadata = is_array($confirmation->metadata) ? $confirmation->metadata : [];
        $racetrack = is_array($confirmation->racetrack) ? $confirmation->racetrack : [];
        $inUse = is_array($confirmation->in_use) ? $confirmation->in_use : [];
        $desired = $this->scopes($analysis, $assay);
        $desiredKeys = [];

        foreach ($desired as $scope) {
            $df = $scope['df'];
            $rep = (string) $scope['rep'];
            $desiredKeys[$df.':'.$rep] = true;

            if (! $scope['applicable']) {
                $metadata[$df][$rep] = [
                    'applicable' => false,
                    'isReady' => true,
                    'ratio' => false,
                ];
                unset($racetrack[$df][$rep], $inUse[$df][$rep]);

                if (($racetrack[$df] ?? []) === []) {
                    unset($racetrack[$df]);
                }

                if (($inUse[$df] ?? []) === []) {
                    unset($inUse[$df]);
                }

                continue;
            }

            $previouslyApplicable = $metadata[$df][$rep]['applicable'] ?? isset($racetrack[$df][$rep]);
            $metadata[$df][$rep] = [
                ...($metadata[$df][$rep] ?? []),
                'applicable' => true,
            ];
            $racetrack[$df][$rep] ??= [];

            if (! $previouslyApplicable) {
                for ($index = 0; $index < $scope['count']; $index++) {
                    $racetrack[$df][$rep][$index] = array_fill(0, count($steps), '');
                }
            }

            $inUse[$df][$rep] ??= [];
        }

        foreach (array_keys($metadata) as $df) {
            foreach (array_keys($metadata[$df]) as $rep) {
                if (! isset($desiredKeys[$df.':'.$rep])) {
                    unset($metadata[$df][$rep], $racetrack[$df][$rep], $inUse[$df][$rep]);
                }
            }

            if ($metadata[$df] === []) {
                unset($metadata[$df]);
            }
        }

        $confirmation->metadata = $metadata;
        $confirmation->racetrack = $racetrack;
        $confirmation->in_use = $inUse;
    }
}
