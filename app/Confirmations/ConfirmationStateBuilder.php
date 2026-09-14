<?php

namespace App\Confirmations;

use App\Models\ConfKeyStore;
use App\Models\Media;
use App\Models\SampleAnalysis;

class ConfirmationStateBuilder
{
    public function __construct(
        private SynchronizeConfirmationScopes $synchronize,
        private ConfirmationRacetrackEvaluator $evaluator,
    ) {}

    public function evaluate(SampleAnalysis $analysis, array $steps, array $support, array $metadata, array $racetrack, array $inUse = []): array
    {
        $media = $this->media($steps, $support);
        $controls = ConfKeyStore::query()->whereIn('media', array_keys($media))->get()->mapWithKeys(
            fn (ConfKeyStore $row): array => [
                $row->innocdate.'|'.$row->media.'|'.$row->param => $row->value,
            ],
        )->all();
        $evaluations = [];
        $supportValues = [];
        $scopeInfo = array_map(function (array $scope) use ($racetrack): array {
            $scopeTrack = $racetrack[$scope['df']][(string) $scope['rep']] ?? $racetrack[$scope['df']][$scope['rep']] ?? [];
            $count = count((array) $scopeTrack);

            return [...$scope, 'applicable' => $count > 0, 'count' => $count];
        }, $this->synchronize->scopes($analysis, $analysis->assayRecord));

        foreach ($scopeInfo as $scope) {
            $key = $scope['df'].':'.$scope['rep'];

            if (! $scope['applicable']) {
                $evaluations[$key] = [
                    'contenders' => [],
                    'metadata_fields' => [],
                    'in_use' => [],
                    'summary' => ['tested' => 0, 'confirmed' => 0, 'ratio' => null, 'scope_ready' => true],
                ];

                continue;
            }

            $scopeMetadata = $metadata[$scope['df']][(string) $scope['rep']] ?? $metadata[$scope['df']][$scope['rep']] ?? [];
            $scopeTrack = $racetrack[$scope['df']][(string) $scope['rep']] ?? $racetrack[$scope['df']][$scope['rep']] ?? [];
            $evaluations[$key] = $this->evaluator->evaluate($steps, $scopeTrack, $scopeMetadata, $media, $controls);
            $supportValues[$key] = $this->supportValues($support, $scope['df'], $scope['rep'], $inUse);
        }

        return [
            'scopeInfo' => $scopeInfo,
            'evaluations' => $evaluations,
            'support' => $supportValues,
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

    private function supportValues(array $support, string $df, int $rep, array $inUse): array
    {
        $scopeValues = $inUse[$df][(string) $rep] ?? $inUse[$df][$rep] ?? [];

        return collect($support)->mapWithKeys(fn (array $row): array => [
            (string) $row['mediaId'] => (bool) ($scopeValues[(string) $row['mediaId']] ?? $scopeValues[$row['mediaId']] ?? false),
        ])->all();
    }
}
