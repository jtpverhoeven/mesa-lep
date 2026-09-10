<?php

namespace App\Actions\Projects;

use App\Models\Project;
use App\Models\ProjectField;

class UpdateProjectFromRegistration
{
	public function handle(Project $project, array $data, int $editedAt): Project
	{
		$currentFields = json_decode($project->custom_fields ?: '{}', true) ?: [];
		$submittedFields = $data['custom_fields'] ?? [];
		$customFields = ProjectField::query()
			->orderBy('position')
			->get(['name', 'std_value'])
			->mapWithKeys(fn (ProjectField $field) => [
				$field->name => $submittedFields[$field->name] ?? $currentFields[$field->name] ?? $field->std_value,
			])
			->all();

		$project->update([
			'project_name' => $data['project_name'],
			'custom_fields' => json_encode($customFields, JSON_FORCE_OBJECT),
			'last_edit' => $editedAt,
		]);

		return $project;
	}
}