<?php

namespace App\Actions\Confirmations;

use App\Models\SampleAnalysis;

class ListRunningConfirmations
{
    /**
     * Unfinished sample analyses with a requested confirmation on a confirmation assay (legacy samples/runningConf).
     *
     * @return list<array{id: int, barcode: string, barcode_specific: string, description: ?string, client_id: ?int, client: ?string, assay_name: ?string, project: ?int}>
     */
    public function handle(): array
    {
        return SampleAnalysis::query()
            ->where('conf_requested', 1)
            ->where('is_ready', 0)
            ->whereHas('assayRecord', fn ($assay) => $assay->where('confirmation', 1))
            ->whereHas('sampleRecord')
            ->with(['assayRecord:id,name', 'sampleRecord:id,barcode,description,client', 'sampleRecord.client:id,name'])
            ->orderBy('id')
            ->get(['id', 'sample', 'assay_base', 'follow_number', 'project'])
            ->map(function (SampleAnalysis $analysis): array {
                $sample = $analysis->sampleRecord;
                $client = $sample->getRelation('client');

                return [
                    'id' => $analysis->id,
                    'barcode' => $sample->barcode,
                    'barcode_specific' => $sample->barcode.'.'.$analysis->follow_number,
                    'description' => $sample->description,
                    'client_id' => $client?->id,
                    'client' => $client?->name,
                    'assay_name' => $analysis->assayRecord->name,
                    'project' => $analysis->project ? (int) $analysis->project : null,
                ];
            })
            ->sortBy('assay_name', SORT_NATURAL | SORT_FLAG_CASE)
            ->values()
            ->all();
    }
}
