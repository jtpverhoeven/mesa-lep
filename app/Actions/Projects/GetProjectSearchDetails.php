<?php

namespace App\Actions\Projects;

use App\Enums\NoteKind;
use App\Keyrings\KeyringType;
use App\Models\Project;
use App\Models\Sample;

class GetProjectSearchDetails
{
    public function __construct(private GetProjectInfo $projectInfo, private GetEditability $editability) {}

    public function handle(Project $project): array
    {
        $info = $this->projectInfo->handle($project);

        $samples = $project->samples()
            ->withExists(['analyses as has_open_analyses' => fn ($analyses) => $analyses->where('is_ready', false)])
            ->withCount('analyses')
            ->withExists([
                'notes as has_internal_note' => fn ($notes) => $notes->ofKind(NoteKind::Internal),
                'notes as has_report_note' => fn ($notes) => $notes->ofKind(NoteKind::Report),
            ])
            ->orderBy('id')
            ->get(['id', 'project', 'barcode', 'description']);

        return [
            'url' => route('projects.search', ['project' => $project->id]),
            'project' => $info['project'],
            'fields' => $info['fields'],
            'project_info' => $info,
            'samples' => $samples->map(fn (Sample $sample, int $index): array => [
                ...$sample->only(['id', 'barcode', 'description']),
                'has_internal_note' => (bool) $sample->has_internal_note,
                'has_report_note' => (bool) $sample->has_report_note,
                'follow' => $index + 1,
                'analyses_count' => $sample->analyses_count,
                'is_ready' => ! $sample->has_open_analyses,
            ])->all(),
            ...$this->editability->handle($project, KeyringType::Project, $project->id),
        ];
    }
}
