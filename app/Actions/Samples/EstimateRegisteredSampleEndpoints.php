<?php

namespace App\Actions\Samples;

use App\Models\Sample;
use App\Support\LaboratoryCalendar;
use Illuminate\Support\Facades\DB;

class EstimateRegisteredSampleEndpoints
{
    public function __construct(private LaboratoryCalendar $calendar) {}

    public function handle(Sample $sample): int
    {
        return DB::transaction(function () use ($sample): int {
            $sample = Sample::query()->lockForUpdate()->findOrFail($sample->getKey());
            $project = $sample->project()->lockForUpdate()->firstOrFail();
            $projectFields = json_decode($project->custom_fields ?: '{}', true) ?: [];
            $sampleFields = json_decode($sample->custom_fields ?: '{}', true) ?: [];
            $endpoints = [0];
            $canCalculate = true;

            foreach ($sample->analyses()->with('assayRecord')->get() as $analysis) {
                $assay = $analysis->assayRecord;
                if ($assay === null) {
                    continue;
                }

                $anchor = match ($assay->start_from) {
                    'r' => (int) $sample->date_registered,
                    'i' => is_numeric($sample->sample_innoculated) && (int) $sample->sample_innoculated > 0 ? (int) $sample->sample_innoculated : -1,
                    default => $this->fieldAnchor((string) $assay->start_from, $projectFields, $sampleFields),
                };
                $endpoint = $anchor === -1 ? -1 : $anchor + (int) ((float) $assay->duration * 86400);
                if ($endpoint > 0) {
                    $endpoint = $this->calendar->getReadDateFromTimestamp($endpoint);
                }
                if ($anchor === -1) {
                    $canCalculate = false;
                }
                $analysis->update(['predicted_end' => $endpoint]);
                $endpoints[] = $endpoint;
            }

            $sample->update(['predicted_end' => $canCalculate ? (max($endpoints) ?: now()->timestamp) : -1]);
            $projectEndpoints = $project->samples()->pluck('predicted_end')->map(fn ($value) => (int) $value);
            $project->update(['predicted_end' => $projectEndpoints->contains(-1) ? -1 : ($projectEndpoints->max() ?: now()->timestamp)]);

            return (int) $sample->predicted_end;
        }, 3);
    }

    private function fieldAnchor(string $startFrom, array $projectFields, array $sampleFields): int
    {
        [$scope, $field] = array_pad(explode(':', $startFrom, 2), 2, '');
        $value = ($scope === 'p' ? $projectFields : $sampleFields)[$field] ?? '';

        return $value === '' ? 0 : (strtotime($value) ?: 0);
    }
}
