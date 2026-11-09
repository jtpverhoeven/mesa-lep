<?php

namespace App\Actions\Projects;

use App\Models\Project;
use App\Models\SampleAnalysis;

class SyncProjectReadyState
{
    /**
     * Mirrors legacy sampleAnalysisController: a project is ready when none of its analyses is open.
     */
    public function handle(int $projectId): void
    {
        $project = Project::query()->find($projectId);

        if ($project === null) {
            return;
        }

        $isReady = ! SampleAnalysis::query()->where('project', $projectId)->where('is_ready', false)->exists();

        if ((int) $project->is_ready === (int) $isReady) {
            return;
        }

        $project->is_ready = $isReady ? 1 : 0;
        $project->became_ready_on = time();
        $project->save();
    }
}
