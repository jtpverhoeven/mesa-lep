<?php

namespace App\Actions\SampleAnalyses;

use App\Calculations\ResultCalculationResolver;
use App\Models\ReferenceSource;
use App\Models\SampleAnalysis;
use Illuminate\Validation\ValidationException;

class GetSampleAnalysisReferences
{
    public function __construct(private ResultCalculationResolver $calculations) {}

    public function handle(SampleAnalysis $analysis): array
    {
        if (! $analysis->isRoaming()) {
            throw ValidationException::withMessages(['analysis' => 'Referentiewaarden kunnen alleen voor losse analyses worden gewijzigd.']);
        }

        $analysis->loadMissing(['assayRecord', 'sampleRecord']);
        $settings = $analysis->roamingSettings()->firstOrFail();
        $outputs = $this->calculations->referenceOutputs($analysis->assayRecord);

        return [
            'analysis_id' => $analysis->id,
            'name' => $analysis->assayRecord->name,
            'reference' => $settings->reference ?? [],
            'reference_source' => $settings->reference_source,
            'configuration' => [
                'reference_outputs' => $outputs === null ? null : collect($outputs)->map(
                    fn (string $label, string $name): array => ['key' => 'ref_'.$name, 'name' => $name, 'label' => $label],
                )->values()->all(),
            ],
            'reference_sources' => ReferenceSource::query()
                ->availableForClient($analysis->sampleRecord->client)
                ->orderBy('id')
                ->get(['id', 'name', 'client'])
                ->map(fn (ReferenceSource $source): array => [
                    'id' => $source->id, 'name' => $source->localizedName(), 'client' => $source->client,
                ])->all(),
        ];
    }
}
