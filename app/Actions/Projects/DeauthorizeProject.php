<?php

namespace App\Actions\Projects;

use App\ChangeTracking\ChangeTracker;
use App\ClientPortal\ClientPortalService;
use App\Models\Project;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Throwable;

class DeauthorizeProject
{
    public function __construct(private ClientPortalService $clientPortal, private ChangeTracker $changeTracker) {}

    /** @return array<string, mixed> */
    public function handle(Project $project, int $userId, string $origin, string $reason): array
    {
        $result = DB::transaction(function () use ($project, $userId, $origin, $reason): array {
            $analyses = $project->sampleAnalyses()->reorder('id')->lockForUpdate()->get(['sampleanalysis.id']);
            $lockedProject = Project::query()->lockForUpdate()->findOrFail($project->getKey());

            if ($analyses->modelKeys() !== $lockedProject->sampleAnalyses()->reorder('id')->pluck('sampleanalysis.id')->all()) {
                throw ValidationException::withMessages([
                    'authorization' => 'Het onderzoek in dit project is gewijzigd. Probeer opnieuw.',
                ]);
            }

            return $this->revoke($lockedProject, $userId, $origin, $reason);
        });

        if ($result['deauthorized']) {
            $result['portal_sync_failed'] = $this->syncPortal($project->id, false);
        }

        return $result;
    }

    /**
     * Revokes the authorisation of a project the caller has already locked, together with its analyses,
     * inside a database transaction. The portal is not notified; call syncPortal() after the commit.
     *
     * @return array<string, mixed>
     */
    public function revoke(Project $lockedProject, int $userId, string $origin, string $reason): array
    {
        if ((int) $lockedProject->auth_status === 0) {
            return [
                'deauthorized' => false,
                'message' => 'Dit project is niet geautoriseerd.',
                'project' => $this->projectState($lockedProject),
            ];
        }

        $from = (string) $lockedProject->auth_status;
        $updatedAt = now()->timestamp;
        $lockedProject->auth_on = 0;
        $lockedProject->auth_status = 0;
        $lockedProject->revision = (int) $lockedProject->revision + 1;
        $lockedProject->last_edit = $updatedAt;
        $lockedProject->rap_stat = 0;
        $lockedProject->save();
        $lockedProject->sampleAnalyses()->update([
            'auth_status' => 0,
            'auth_by' => $userId,
            'storedResult' => null,
        ]);

        $this->changeTracker->changed(9, project: $lockedProject->id,
            event: 'Project authorisatie ingetrokken, bron wijziging: '.$origin.', reden:'.$reason,
            from: $from, to: '0', userId: $userId);

        return [
            'deauthorized' => true,
            'project' => $this->projectState($lockedProject),
        ];
    }

    /** Returns whether the portal could not be informed; the legacy preview flag marks a temporary-report deauthorisation. */
    public function syncPortal(int $projectId, bool $temporaryReport): bool
    {
        try {
            return ! $this->clientPortal->projectDeauthorized($projectId, $temporaryReport)->successful();
        } catch (Throwable $exception) {
            report($exception);

            return true;
        }
    }

    /** @return array<string, mixed> */
    private function projectState(Project $project): array
    {
        return $project->only([
            'id', 'auth_status', 'auth_by', 'auth_on', 'revision', 'rap_stat', 'last_edit', 'locked', 'lock_message',
        ]);
    }
}
