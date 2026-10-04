<?php

namespace App\Actions\Projects;

use App\Models\Project;
use Carbon\CarbonInterface;
use Illuminate\Validation\ValidationException;

class ResolveSampleRegistrationProject
{
    public function __construct(private CreateProject $create, private UpdateProjectFromRegistration $update) {}

    public function handle(array $data, string $barcode, CarbonInterface $at): Project
    {
        if (! empty($data['project'])) {
            $project = Project::query()->whereKey($data['project'])->where('client', $data['client'])->lockForUpdate()->first();

            if ($project === null) {
                throw ValidationException::withMessages(['project' => 'Het gekozen project hoort niet bij deze klant.']);
            }

            if (isset($data['special_type']) && ((int) $project->special_type !== $data['special_type'] || $project->auth_status || $project->locked)) {
                throw ValidationException::withMessages(['project' => 'Kies een open project van het juiste monstertype.']);
            }

            $project = $this->update->handle($project, [
                'project_name' => $data['project_name'] ?? $project->project_name,
                'custom_fields' => $data['project_custom_fields'] ?? [],
            ], $at->timestamp);

            if (($data['special_type'] ?? 0) === 2) {
                $extra = json_decode($project->project_extra ?: '{}', true) ?: [];
                $project->update(['project_extra' => json_encode([...$extra, 'number_of_blanks' => '0'], JSON_FORCE_OBJECT)]);
            }

            return $project;
        }

        $project = $this->create->handle([
            'client' => $data['client'],
            'project_name' => ($data['project_name'] ?? '') ?: $barcode,
            'custom_fields' => $data['project_custom_fields'] ?? [],
            'added_by' => $data['registered_by'],
            'special_type' => $data['special_type'] ?? 0,
            'project_extra' => $data['project_extra'] ?? null,
        ], $at);

        if (($data['special_type'] ?? 0) !== 0) {
            $project->update(['last_edit' => $at->timestamp]);
        }

        return $project;
    }
}
