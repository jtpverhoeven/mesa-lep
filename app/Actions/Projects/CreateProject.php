<?php

namespace App\Actions\Projects;

use App\Models\Project;
use App\Models\ProjectField;
use Carbon\CarbonInterface;

class CreateProject
{
    public function handle(array $data, CarbonInterface $at): Project
    {
        return Project::create([
            'client' => $data['client'],
            'subclient' => 0,
            'project_name' => $data['project_name'],
            'project_date' => (string) $at->timestamp,
            'custom_fields' => json_encode($this->customFields($data['custom_fields'] ?? [], $at), JSON_FORCE_OBJECT),
            'revision' => 1,
            'special_type' => 0,
            'predicted_end' => $at->timestamp,
            'added_by' => $data['added_by'],
        ]);
    }

    private function customFields(array $values, CarbonInterface $at): array
    {
        return ProjectField::query()
            ->orderBy('position')
            ->get(['name', 'std_value'])
            ->mapWithKeys(function (ProjectField $field) use ($values, $at) {
                return [$field->name => $values[$field->name] ?? $this->defaultValue($field->std_value, $at)];
            })
            ->all();
    }

    private function defaultValue(string $value, CarbonInterface $at): string
    {
        return match ($value) {
            '{today}' => $at->format('d-m-Y'),
            '{tommorow}' => $at->copy()->addDay()->format('d-m-Y'),
            '{yesterday}' => $at->copy()->subDay()->format('d-m-Y'),
            '{current_time}' => $at->format('H:i'),
            default => $value,
        };
    }
}