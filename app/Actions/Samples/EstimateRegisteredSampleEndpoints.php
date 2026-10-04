<?php

namespace App\Actions\Samples;

use App\Models\Sample;
use Carbon\CarbonImmutable;

class EstimateRegisteredSampleEndpoints
{
    public function handle(Sample $sample): void
    {
        $project = $sample->project()->firstOrFail();
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
                'i' => empty($sample->sample_innoculated) ? -1 : (int) $sample->sample_innoculated,
                default => $this->fieldAnchor((string) $assay->start_from, $projectFields, $sampleFields),
            };
            $endpoint = $anchor === -1 ? -1 : $anchor + (int) ((float) $assay->duration * 86400);
            if ($endpoint > 0) {
                $endpoint = $this->readDate($endpoint)->timestamp;
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
    }

    private function fieldAnchor(string $startFrom, array $projectFields, array $sampleFields): int
    {
        [$scope, $field] = array_pad(explode(':', $startFrom, 2), 2, '');
        $value = ($scope === 'p' ? $projectFields : $sampleFields)[$field] ?? '';

        return $value === '' ? 0 : (strtotime($value) ?: 0);
    }

    private function readDate(int $timestamp): CarbonImmutable
    {
        $date = CarbonImmutable::parse(CarbonImmutable::createFromTimestampUTC($timestamp)->format('Y-m-d'), config('app.timezone'));
        while ($date->isWeekend() || $this->isHoliday($date)) {
            $date = $date->addDay();
        }

        return $date;
    }

    private function isHoliday(CarbonImmutable $date): bool
    {
        $year = $date->year;
        $easter = CarbonImmutable::create($year, 3, 21, 0, 0, 0, 'Europe/Amsterdam')->addDays(easter_days($year));
        $kingsDay = CarbonImmutable::create($year, 4, 27);
        if ($kingsDay->isSunday()) {
            $kingsDay = $kingsDay->subDay();
        }
        $holidays = ['01-01', '12-25', '12-26', $kingsDay->format('m-d')];
        foreach ([0, 1, 39, 49, 50] as $offset) {
            $holidays[] = $easter->addDays($offset)->format('m-d');
        }
        if ($year % 5 === 0) {
            $holidays[] = '05-05';
        }

        return in_array($date->format('m-d'), $holidays, true);
    }
}
