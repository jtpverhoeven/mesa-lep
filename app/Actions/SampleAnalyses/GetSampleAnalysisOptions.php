<?php

namespace App\Actions\SampleAnalyses;

use App\Models\Assay;
use App\Models\AssayProfile;
use App\Models\Matrix;
use App\Models\ReferenceSource;
use App\Models\ResearchProfile;

class GetSampleAnalysisOptions
{
    public function handle(int $clientId): array
    {
        $profiles = ResearchProfile::query()
            ->where('active', 1)
            ->where('lims_visible', 1)
            ->where(fn ($query) => $query->where('global', 1)->orWhere('client', $clientId))
            ->with([
                'assayProfiles' => fn ($query) => $query->where('hidden', 0),
                'assayProfiles.assayRecord:id,original_id,name,type,type_base,dillution,replicates',
            ])
            ->orderBy('global')
            ->orderBy('name')
            ->get(['id', 'name', 'global', 'client'])
            ->map(fn (ResearchProfile $profile) => [
                ...$profile->only(['id', 'name', 'global', 'client']),
                'assays' => $profile->assayProfiles->map(fn (AssayProfile $item) => [
                    ...$item->only([
                        'id', 'assay', 'dillutions', 'replicates', 'reference',
                        'reference_source', 'project_order',
                    ]),
                    'name' => $item->assayRecord?->name,
                    'type' => $item->assayRecord?->type,
                    'type_base' => $item->assayRecord?->type_base,
                    'dillution_enabled' => (bool) $item->assayRecord?->dillution,
                    'replicates_enabled' => (bool) $item->assayRecord?->replicates,
                ])->values(),
            ]);

        $assays = Assay::query()
            ->with('matrixContents:assay_base,matrix')
            ->where('active', 1)
            ->orderBy('name')
            ->get([
                'id', 'original_id', 'name', 'type', 'type_base', 'dillution',
                'replicates', 'custom_fields',
            ])
            ->map(function (Assay $assay) {
                $customFields = json_decode($assay->custom_fields ?: '{}', true) ?: [];

                return [
                    ...$assay->only([
                        'id', 'original_id', 'name', 'type', 'type_base', 'dillution', 'replicates',
                    ]),
                    'accreditation' => strtolower((string) ($customFields['accred'] ?? '')),
                    'matrices' => $assay->matrixContents->pluck('matrix')->values(),
                ];
            });

        $referenceSources = ReferenceSource::query()
            ->availableForClient($clientId)
            ->orderBy('id')
            ->get(['id', 'name', 'client'])
            ->map(fn (ReferenceSource $source) => [
                'id' => $source->id,
                'name' => $source->localizedName(),
                'client' => $source->client,
            ]);

        return [
            'profiles' => $profiles,
            'assays' => $assays,
            'matrices' => Matrix::query()->orderBy('name')->get(['id', 'name']),
            'reference_sources' => $referenceSources,
        ];
    }
}
