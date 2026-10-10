<?php

namespace App\Actions\Projects;

use App\ChangeTracking\ChangeTracker;
use App\Models\Project;
use App\Models\ProjectField;
use Illuminate\Support\Facades\DB;

class UpdateProjectFromRegistration
{
    public function __construct(private ChangeTracker $changeTracker) {}

    public function handle(Project $project, array $data, int $editedAt, ?int $userId = null): Project
    {
        return DB::transaction(function () use ($project, $data, $editedAt, $userId): Project {
            $currentFields = json_decode($project->custom_fields ?: '{}', true) ?: [];
            $submittedFields = $data['custom_fields'] ?? [];
            $customFields = ProjectField::query()
                ->orderBy('position')
                ->get(['name', 'std_value'])
                ->mapWithKeys(fn (ProjectField $field) => [
                    $field->name => $submittedFields[$field->name] ?? $currentFields[$field->name] ?? $field->std_value,
                ])
                ->all();

            $previousName = $project->project_name;
            $project->update([
                'project_name' => $data['project_name'],
                'custom_fields' => json_encode($customFields, JSON_FORCE_OBJECT),
                'last_edit' => $editedAt,
            ]);
            $changes = ['project_name' => [$previousName, $data['project_name']]];
            foreach ($customFields as $field => $value) {
                $changes[$field] = [$currentFields[$field] ?? '', $value];
            }
            foreach ($changes as $field => [$previous, $value]) {
                if ((string) $previous !== (string) $value) {
                    $this->changeTracker->changed(17, project: $project->id,
                        event: 'Project informatie gewijzigd: '.$field, from: $previous, to: $value, userId: $userId);
                }
            }

            return $project;
        });
    }
}
