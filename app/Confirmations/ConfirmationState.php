<?php

namespace App\Confirmations;

use App\Actions\Confirmations\RecalculateConfirmation;
use App\Models\Confirmation;
use App\Models\Media;
use App\Models\SampleAnalysis;

class ConfirmationState
{
    public function build(
        SampleAnalysis $analysis,
        ?Confirmation $confirmation = null,
        ?string $selectedDf = null,
        int $selectedRep = 0,
    ): array {
        $analysis->loadMissing(['assayRecord', 'projectRecord', 'results']);
        $assay = $analysis->assayRecord;
        $confirmation ??= new Confirmation(['said' => $analysis->id]);

        if ($assay === null) {
            return [
                'analysis_id' => (int) $analysis->id,
                'decision' => (int) $analysis->conf_requested,
                'status' => 'not_applicable',
                'read_only' => (bool) ($analysis->projectRecord?->locked || $analysis->projectRecord?->auth_status),
                'decision_required' => false,
                'config' => ['mode' => 'global', 'depth' => 0, 'steps' => []],
                'selected_scope' => ['df' => 'global', 'rep' => 0],
                'scopes' => [],
                'contenders' => [],
                'metadata_fields' => [],
                'support_fields' => [],
                'summary' => ['tested' => 0, 'confirmed' => 0, 'ratio' => null, 'scope_ready' => false, 'all_ready' => false],
                'note' => (string) ($confirmation->note ?? ''),
            ];
        }

        $snapshot = clone $confirmation;
        $evaluation = app(RecalculateConfirmation::class)->handle($analysis, $snapshot, false);
        $steps = AssayConfirmationConfiguration::decode($assay?->confirmation_script);
        $support = AssayConfirmationConfiguration::decode($assay?->confirmation_support);
        $scopes = [];

        foreach ($evaluation['scopeInfo'] as $scope) {
            $key = $this->scopeKey($scope['df'], $scope['rep']);
            $summary = $evaluation['evaluations'][$key]['summary'] ?? [
                'tested' => 0,
                'confirmed' => 0,
                'ratio' => null,
                'scope_ready' => false,
            ];
            $scopes[$key] = [
                'df' => $scope['df'],
                'rep' => $scope['rep'],
                'ready' => $scope['applicable'] ? (bool) $summary['scope_ready'] : true,
                'applicable' => $scope['applicable'],
                'ratio' => $summary['ratio'],
            ];
        }

        $selectedKey = $this->scopeKey($selectedDf ?? '', $selectedRep);
        if (! isset($scopes[$selectedKey])) {
            $selectedKey = array_key_first($scopes) ?? 'global:0';
        }

        $selectedScope = $scopes[$selectedKey] ?? [
            'df' => 'global',
            'rep' => 0,
            'ready' => false,
            'applicable' => false,
            'ratio' => null,
        ];
        $selectedEvaluation = $evaluation['evaluations'][$selectedKey] ?? [
            'contenders' => [],
            'metadata_fields' => [],
            'in_use' => [],
            'summary' => ['tested' => 0, 'confirmed' => 0, 'ratio' => null, 'scope_ready' => false],
        ];
        $media = $this->media($steps, $support);
        $decision = (int) $analysis->conf_requested;
        $configured = $assay !== null && (int) $assay->confirmation === 1;
        $calculatedStatus = $analysis->storedResult['confirmation']['status'] ?? null;
        $status = ! $configured
            ? 'not_applicable'
            : match ($decision) {
                2 => 'disabled',
                1 => $confirmation->isReady ? 'enabled_complete' : 'enabled_pending',
                default => $calculatedStatus === 'decision_pending' ? 'decision_pending' : 'not_applicable',
            };

        return [
            'analysis_id' => (int) $analysis->id,
            'decision' => $decision,
            'status' => $status,
            'read_only' => (bool) ($analysis->projectRecord?->locked || $analysis->projectRecord?->auth_status),
            'decision_required' => $status === 'decision_pending',
            'config' => [
                'mode' => (int) ($assay?->confirmation_type ?? 0) === 0 ? 'global' : 'per_plate',
                'depth' => (int) ($assay?->confirmation_depth ?? 0),
                'steps' => collect($steps)->values()->map(function (array $step, int $index) use ($media): array {
                    $mediaId = (int) ($step['mediaId'] ?? 0);

                    return [
                        'index' => $index,
                        'chain_id' => (int) ($step['chainId'] ?? $index + 1),
                        'media_id' => $mediaId,
                        'name' => $media[(string) $mediaId]['name'] ?? 'Medium '.$mediaId,
                        'disposition' => $step['disposition'] ?? '',
                    ];
                })->all(),
            ],
            'selected_scope' => ['df' => $selectedScope['df'], 'rep' => $selectedScope['rep']],
            'scopes' => array_values($scopes),
            'contenders' => $selectedEvaluation['contenders'],
            'metadata_fields' => $selectedEvaluation['metadata_fields'],
            'support_fields' => collect($support)->values()->map(function (array $row) use ($selectedScope, $media, $evaluation): array {
                $scopeKey = $this->scopeKey($selectedScope['df'], $selectedScope['rep']);
                $active = $evaluation['support'][$scopeKey][(string) $row['mediaId']] ?? false;
                $assuranceField = collect($evaluation['evaluations'][$scopeKey]['metadata_fields'] ?? [])
                    ->firstWhere('key', (int) $row['mediaId'].'_tht');

                return [
                    'media_id' => (int) $row['mediaId'],
                    'name' => $media[(string) $row['mediaId']]['name'] ?? 'Medium '.$row['mediaId'],
                    'active' => (bool) $active,
                    'assurance' => $assuranceField,
                ];
            })->all(),
            'summary' => [
                ...$selectedEvaluation['summary'],
                'all_ready' => (bool) $confirmation->isReady,
            ],
            'note' => (string) ($confirmation->note ?? ''),
        ];
    }

    private function media(array $steps, array $support): array
    {
        $ids = collect([...$steps, ...$support])
            ->pluck('mediaId')
            ->filter(fn ($id): bool => is_numeric($id))
            ->map(fn ($id): int => (int) $id)
            ->unique()
            ->values();

        return Media::query()->whereIn('id', $ids)->get()->mapWithKeys(fn (Media $media): array => [
            (string) $media->id => [
                'name' => $media->name,
                'hasDate' => $media->hasDate,
                'confirmation_controls' => $media->confirmation_controls,
            ],
        ])->all();
    }

    private function scopeKey(string $df, int $rep): string
    {
        return $df.':'.$rep;
    }
}
