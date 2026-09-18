<?php

namespace App\Actions\Projects;

use App\Models\Project;
use App\Models\Sample;
use App\Models\SampleField;
use App\Models\SampleProcedure;

class GetProjectSearchSample
{
    public function handle(Project $project, Sample $sample): array
    {
        abort_unless((int) $sample->project === (int) $project->id, 404);

        $sample->load([
            'analyses.assayRecord',
            'analyses.researchProfile',
            'metadata',
        ]);

        $sampleIds = $project->samples()->orderBy('id')->pluck('id');
        $sampleFields = SampleField::query()->orderBy('position')->orderBy('id')->get(['name', 'alias', 'type']);

        return [
            'sample' => $sample->only([
                'id', 'barcode', 'description', 'client_description', 'sampling_method',
            ]),
            'project_follow_number' => $sampleIds->search(fn ($id): bool => (int) $id === (int) $sample->id) + 1,
            'sampling_methods' => SampleProcedure::query()
                ->where('active', 1)
                ->orderBy('name')
                ->get(['id', 'name']),
            'sample_fields' => $this->fields($sample->custom_fields, $sampleFields),
            'sample_extra' => collect($this->fields($sample->sample_extra, collect([
                (object) ['name' => 'type', 'alias' => 'Leiding', 'type' => 'select'],
                (object) ['name' => 'temperature', 'alias' => 'Temperatuur', 'type' => 'text'],
                (object) ['name' => 'location', 'alias' => 'Ruimte', 'type' => 'text'],
                (object) ['name' => 'filter_volume', 'alias' => 'Onderzocht volume in ml.', 'type' => 'number'],
            ])))
                ->reject(fn (array $field): bool => $field['name'] === 'follow')
                ->values()
                ->all(),
            'metadata' => $sample->metadata->map->only([
                'id', 'sample', 'name', 'value', 'meta_data_key_id', 'meta_order',
            ])->values()->all(),
            'analyses' => $sample->analyses->map(fn ($analysis): array => [
                ...$analysis->only([
                    'id', 'profile_group', 'profile', 'follow_number', 'conf_requested', 'is_ready',
                ]),
                'name' => $analysis->assayRecord?->name ?? 'Onbekende analyse',
                'profile_name' => $analysis->isRoaming()
                    ? null
                    : ($analysis->researchProfile?->name ?? 'Onbekend profiel'),
            ])->values()->all(),
            'read_only' => (bool) ($project->auth_status || $project->locked),
        ];
    }

    private function fields(?string $json, $definitions): array
    {
        $values = json_decode($json ?: '{}', true);
        $values = is_array($values) ? $values : [];
        $names = $definitions->pluck('name')->merge(array_keys($values))->unique();

        return $names->map(function (string $name) use ($definitions, $values): array {
            $definition = $definitions->firstWhere('name', $name);

            return [
                'name' => $name,
                'label' => $definition?->alias ?: $name,
                'type' => $definition?->type ?: 'text',
                'value' => $values[$name] ?? '',
            ];
        })->values()->all();
    }
}
