<?php

namespace App\Actions\Projects;

use App\ChangeTracking\ChangeTracker;
use App\Events\ProjectEditabilityChanged;
use App\Models\Project;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class UnblockProjectAuthorization
{
    public const CHANGE_TYPE = 23;

    public function __construct(private ChangeTracker $changeTracker) {}

    /**
     * Clears the authorization block. This deliberately bypasses the project rule, because the block itself makes the project read-only.
     */
    public function handle(Project $project, int $userId): Project
    {
        return DB::transaction(function () use ($project, $userId): Project {
            $lockedProject = Project::query()->lockForUpdate()->findOrFail($project->getKey());

            if (! $lockedProject->isAuthorizationBlocked()) {
                throw ValidationException::withMessages(['project' => 'De autorisatie van dit project is niet geblokkeerd.']);
            }

            $lockedProject->update([
                'locked' => 0,
                'locked_by' => null,
                'lock_message' => null,
            ]);
            $this->changeTracker->changed(self::CHANGE_TYPE, project: $lockedProject->id,
                event: 'Projectautorisatie vrijgegeven', from: '1', to: '0', userId: $userId);
            ProjectEditabilityChanged::dispatch($lockedProject->id, $lockedProject->isReadOnly(), $lockedProject->readOnlyReasons(), null);

            return $lockedProject;
        });
    }
}
