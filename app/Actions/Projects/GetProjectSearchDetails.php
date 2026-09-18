<?php

namespace App\Actions\Projects;

use App\Models\Project;
use App\Models\ProjectField;
use App\Models\Sample;

class GetProjectSearchDetails
{
    public function handle(Project $project): array
    {
        $project->load('client:id,name');

        $samples = $project->samples()
            ->withExists(['analyses as has_open_analyses' => fn ($analyses) => $analyses->where('is_ready', false)])
            ->withCount('analyses')
            ->orderBy('id')
            ->get(['id', 'project', 'barcode', 'description', 'sample_note']);

        return [
            'project' => [
                ...$project->only([
                    'id', 'reference', 'project_name', 'project_date', 'revision', 'auth_status',
                    'is_ready', 'started', 'locked', 'lock_message', 'project_notes',
                ]),
                'client_name' => $project->getRelation('client')?->name,
            ],
            'fields' => $this->fields($project->custom_fields),
            'samples' => $samples->map(fn (Sample $sample, int $index): array => [
                ...$sample->only(['id', 'barcode', 'description', 'sample_note']),
                'follow' => $index + 1,
                'analyses_count' => $sample->analyses_count,
                'is_ready' => ! $sample->has_open_analyses,
            ])->all(),
        ];
    }

    private function fields(?string $json): array
    {
        $values = json_decode($json ?: '{}', true);
        $values = is_array($values) ? $values : [];
        $definitions = ProjectField::query()->orderBy('position')->get(['name', 'alias']);

        return $definitions
            ->concat(collect(array_keys($values))->diff($definitions->pluck('name'))->map(
                fn (string $name): object => (object) ['name' => $name, 'alias' => $name],
            ))
            ->map(fn ($field): array => [
                'name' => $field->name,
                'label' => $field->alias ?: $field->name,
                'value' => $values[$field->name] ?? '',
            ])
            ->filter(fn (array $field): bool => $field['value'] !== '')
            ->values()
            ->all();
    }
}
