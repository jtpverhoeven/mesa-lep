<?php

namespace App\Actions\Samples;

use App\Models\ProjectField;
use App\Models\Sample;
use App\Models\SampleField;
use App\Models\SampleProcedure;
use App\Models\User;

class LookupSample
{
    public function handle(string $barcode): array
    {
        $sample = Sample::query()->where('barcode', $barcode)->with([
            'client', 'project', 'analyses.assayRecord', 'analyses.researchProfile',
            'analyses.assayProfile', 'analyses.roamingAnalysis',
        ])->firstOrFail();
        $project = $sample->getRelation('project');
        $client = $sample->getRelation('client');
        $projectSamples = $project?->samples()->orderBy('id')->get(['id', 'barcode', 'description']) ?? collect();

        return [
            'sample' => $sample->only([
                'id', 'barcode', 'description', 'client_description', 'client', 'project',
                'date_registered', 'predicted_end', 'sample_innoculated', 'stored_in',
                'diluted_at', 'sample_note', 'sample_type', 'tht_code', 'source',
                'portal_sample_id', 'portal_product_group_id', 'portal_project_id', 'portal_notes',
            ]),
            'sample_fields' => $this->fields($sample->custom_fields, SampleField::query()->orderBy('position')->get()),
            'sample_extra' => collect($this->fields($sample->sample_extra, collect([
                (object) ['name' => 'type', 'alias' => 'Tappunt type'],
                (object) ['name' => 'temperature', 'alias' => 'Temperatuur'],
                (object) ['name' => 'location', 'alias' => 'Ruimte'],
                (object) ['name' => 'filter_volume', 'alias' => 'Onderzocht volume in ml.'],
            ])))->reject(fn ($field) => $field['name'] === 'follow')->values()->all(),
            'project_follow_number' => $projectSamples->search(fn ($item) => $item->id === $sample->id) + 1,
            'sampling_method' => SampleProcedure::query()->whereKey($sample->sampling_method)->value('name'),
            'registered_by' => User::query()->whereKey($sample->registered_by)->value('name'),
            'client' => $client?->only(['id', 'name']),
            'project' => $project?->only(['id', 'project_name', 'reference', 'project_date', 'project_notes', 'auth_status', 'is_ready', 'locked', 'lock_message']),
            'project_fields' => $this->fields($project?->custom_fields, ProjectField::query()->orderBy('position')->get()),
            'project_samples' => $projectSamples,
            'analyses' => $sample->analyses->map(fn ($analysis) => [
                ...$analysis->only(['id', 'profile_group', 'profile', 'assay', 'assay_base', 'roaming_id', 'follow_number', 'project_order', 'predicted_end', 'conf_requested', 'is_ready']),
                'name' => $analysis->assayRecord?->name ?? 'Onbekende analyse',
                'profile_name' => $analysis->isRoaming() ? ($analysis->assayRecord?->name ?? 'Losse analyse') : ($analysis->researchProfile?->name ?? 'Onbekend profiel'),
                'settings' => ($analysis->isRoaming() ? $analysis->roamingAnalysis : $analysis->assayProfile)?->only(['dillutions', 'replicates', 'reference', 'reference_scope', 'reference_source']),
            ]),
            'previous' => Sample::query()->where('id', '<', $sample->id)->orderByDesc('id')->value('barcode'),
            'next' => Sample::query()->where('id', '>', $sample->id)->orderBy('id')->value('barcode'),
        ];
    }

    private function fields(?string $json, $definitions): array
    {
        $values = json_decode($json ?: '{}', true);
        $values = is_array($values) ? $values : [];

        $names = $definitions->pluck('name')->merge(array_keys($values))->unique();

        return $names->map(fn ($name) => [
            'name' => $name,
            'label' => $definitions->firstWhere('name', $name)?->alias ?: $name,
            'value' => $values[$name] ?? '',
        ])->values()->all();
    }
}
