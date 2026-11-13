<?php

namespace App\Actions\Printing;

use App\Actions\Samples\FindScannedSample;
use App\Models\Project;
use App\Models\ProjectField;
use App\Models\Sample;
use App\Models\SampleField;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\ValidationException;

class SelectManualPrintSamples
{
    public function __construct(private FindScannedSample $findSample) {}

    /** @param array<string, mixed> $options */
    public function handle(array $options): Builder
    {
        if (($options['mode'] ?? 'sample_range') === 'scan') {
            $sample = $this->findSample->handle($options['barcode']);

            if ($sample === null) {
                throw ValidationException::withMessages(['barcode' => 'Geen monster gevonden voor deze barcode.']);
            }

            return Sample::query()->whereKey($sample->id);
        }

        if (($options['mode'] ?? '') === 'date_range') {
            return $this->dateRange($options);
        }

        $start = explode('.', $options['bar_range_start'])[0];
        $stop = explode('.', $options['bar_range_stop'])[0];
        $first = $this->findSample->handle($start);
        $last = $this->findSample->handle($stop);

        if ($first === null || $last === null || $first->sample_type !== $last->sample_type || $first->leg_type !== $last->leg_type || $this->findSample->isShorthand($start) !== $this->findSample->isShorthand($stop)) {
            throw ValidationException::withMessages(['bar_range_stop' => 'Geen geldig monsterbereik: controleer beide barcodes, monstertype en matrix.']);
        }

        $types = [];

        if ($options['print_normal_samples'] ?? false) {
            $types = ['S', 'L'];
        }

        if ($options['print_rodac_samples'] ?? false) {
            $types[] = 'R';
        }

        return Sample::query()->whereBetween('id', [min($first->id, $last->id), max($first->id, $last->id)])
            ->where('sample_type', $first->sample_type)->where('leg_type', $first->leg_type)
            ->whereIn('sample_type', $types)->orderBy('id');
    }

    /** @return list<array{id: string, name: string}> */
    public function dateTypes(): array
    {
        $types = [['id' => '0', 'name' => 'Registratie datum'], ['id' => 'i', 'name' => 'Inzetdatum']];

        foreach ([['p_', 'Project: ', ProjectField::class], ['s_', 'Monster: ', SampleField::class]] as [$prefix, $title, $model]) {
            foreach ($model::query()->where('type', 'date')->orderBy('position')->orderBy('id')->get() as $field) {
                $types[] = ['id' => $prefix.$field->name, 'name' => $title.$field->alias];
            }
        }

        return $types;
    }

    /** @param array<string, mixed> $options */
    private function dateRange(array $options): Builder
    {
        $start = CarbonImmutable::createFromFormat('!Y-m-d', $options['range_start'])->getTimestamp();
        $end = CarbonImmutable::createFromFormat('!Y-m-d', $options['range_stop'])->getTimestamp() + 23 * 3600;
        $type = $options['range_type_select'];
        $roughStart = $start - 365 * 82800;
        $roughEnd = $end + 365 * 82800;

        if (str_starts_with($type, 'p_')) {
            $projects = Project::query()->where('project_date', '>', $roughStart)->where('project_date', '<', $roughEnd)
                ->lazy()->filter(fn (Project $project): bool => $this->customDateMatches($project->custom_fields, substr($type, 2), $start, $end))->pluck('id')->all();

            return Sample::query()->whereIn('project', $projects)->orderBy('project')->orderBy('id');
        }

        $samples = Sample::query()->where('date_registered', '>', (string) $roughStart)->where('date_registered', '<', (string) $roughEnd);
        $ids = $samples->lazy()->filter(function (Sample $sample) use ($type, $start, $end): bool {
            if (str_starts_with($type, 's_')) {
                return $this->customDateMatches($sample->custom_fields, substr($type, 2), $start, $end);
            }

            $date = (int) ($type === 'i' ? $sample->sample_innoculated : $sample->date_registered);

            return $date >= $start && $date <= $end;
        })->pluck('id')->all();

        return Sample::query()->whereKey($ids)->orderBy('id');
    }

    private function customDateMatches(?string $json, string $field, int $start, int $end): bool
    {
        $fields = json_decode($json ?: '{}', true);
        $value = is_array($fields) ? ($fields[$field] ?? null) : null;
        $date = is_string($value) && $value !== '' ? strtotime($value) : false;

        return $date !== false && $date >= $start && $date <= $end;
    }
}
