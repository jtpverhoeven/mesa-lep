<?php

namespace App\Actions\Projects;

use App\ChangeTracking\ChangeTracker;
use App\Events\ProjectEditabilityChanged;
use App\Models\Project;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class BlockProjectAuthorization
{
    public const CHANGE_TYPE = 22;

    public function __construct(private ChangeTracker $changeTracker) {}

    /**
     * Sets the authorization block. This deliberately bypasses the project rule, because the block itself makes the project read-only.
     */
    public function handle(Project $project, int $userId, ?string $message): Project
    {
        return DB::transaction(function () use ($project, $userId, $message): Project {
            $lockedProject = Project::query()->lockForUpdate()->findOrFail($project->getKey());

            if ($lockedProject->isAuthorized()) {
                throw ValidationException::withMessages(['project' => 'Een geautoriseerd project kan niet worden geblokkeerd.']);
            }

            if ($lockedProject->isAuthorizationBlocked()) {
                throw ValidationException::withMessages(['project' => 'De autorisatie van dit project is al geblokkeerd.']);
            }

            $lockedProject->update([
                'locked' => 1,
                'locked_by' => $userId,
                'lock_message' => $message,
            ]);
            $this->changeTracker->changed(self::CHANGE_TYPE, project: $lockedProject->id,
                event: 'Projectautorisatie geblokkeerd'.($message ? ': '.$message : ''), from: '0', to: '1', userId: $userId);
            ProjectEditabilityChanged::dispatch($lockedProject->id, $lockedProject->isReadOnly(), $lockedProject->readOnlyReasons(), $lockedProject->lock_message);

            return $lockedProject;
        });
    }
}
