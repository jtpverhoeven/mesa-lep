<?php

namespace App\Actions\Confirmations;

use App\Confirmations\AssayConfirmationConfiguration;
use App\Confirmations\ConfirmationStateBuilder;
use App\Confirmations\SynchronizeConfirmationScopes;
use App\Models\Confirmation;
use App\Models\SampleAnalysis;

class RecalculateConfirmation
{
    public function __construct(
        private SynchronizeConfirmationScopes $synchronize,
        private ConfirmationStateBuilder $stateBuilder,
    ) {}

    public function handle(SampleAnalysis $analysis, Confirmation $confirmation, bool $persist = true): array
    {
        $analysis->loadMissing(['assayRecord', 'results', 'confirmationRecord']);
        $this->synchronize->handle($analysis, $confirmation);

        $assay = $analysis->assayRecord;
        $steps = AssayConfirmationConfiguration::decode($assay?->confirmation_script);
        $support = AssayConfirmationConfiguration::decode($assay?->confirmation_support);
        $metadata = is_array($confirmation->metadata) ? $confirmation->metadata : [];
        $racetrack = is_array($confirmation->racetrack) ? $confirmation->racetrack : [];
        $inUse = is_array($confirmation->in_use) ? $confirmation->in_use : [];
        $evaluation = $this->stateBuilder->evaluate($analysis, $steps, $support, $metadata, $racetrack, $inUse);
        $allReady = $evaluation['scopeInfo'] !== [];

        foreach ($evaluation['scopeInfo'] as $scope) {
            $key = $scope['df'].':'.$scope['rep'];
            $summary = $evaluation['evaluations'][$key]['summary'];
            $metadata[$scope['df']][(string) $scope['rep']]['applicable'] = $scope['applicable'];
            $metadata[$scope['df']][(string) $scope['rep']]['isReady'] = $scope['applicable']
                ? (bool) $summary['scope_ready']
                : true;
            $metadata[$scope['df']][(string) $scope['rep']]['ratio'] = $summary['ratio'] ?? false;
            $inUse[$scope['df']][(string) $scope['rep']] = array_replace(
                $evaluation['evaluations'][$key]['in_use'] ?? [],
                $evaluation['support'][$key] ?? [],
            );
            $allReady = $allReady && $metadata[$scope['df']][(string) $scope['rep']]['isReady'];
        }

        $confirmation->metadata = $metadata;
        $confirmation->in_use = $inUse;
        $confirmation->isReady = $allReady;

        if ($persist) {
            $confirmation->save();
        }

        return [
            'confirmation' => $confirmation,
            ...$evaluation,
        ];
    }
}
