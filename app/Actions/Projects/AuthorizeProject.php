<?php

namespace App\Actions\Projects;

use App\Actions\Results\CalculateAnalysisResult;
use App\ClientPortal\ClientPortalService;
use App\Models\Project;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Throwable;

class AuthorizeProject
{
    public function __construct(
        private CheckProjectAuthorization $checkProjectAuthorization,
        private CalculateAnalysisResult $calculateAnalysisResult,
        private ClientPortalService $clientPortal,
    ) {}

    /** @return array<string, mixed> */
    public function handle(Project $project, int $userId, bool $quiet, bool $overrideIncomplete): array
    {
        $result = DB::transaction(function () use ($project, $userId, $overrideIncomplete): array {
            $analyses = $project->sampleAnalyses()->reorder('id')->lockForUpdate()->get(['sampleanalysis.id']);
            $lockedProject = Project::query()->lockForUpdate()->findOrFail($project->getKey());

            if ($analyses->modelKeys() !== $lockedProject->sampleAnalyses()->reorder('id')->pluck('sampleanalysis.id')->all()) {
                throw ValidationException::withMessages([
                    'authorization' => 'Het onderzoek in dit project is gewijzigd. Probeer opnieuw.',
                ]);
            }

            if ((int) $lockedProject->auth_status !== 0) {
                return [
                    'authorized' => false,
                    'blocked' => false,
                    'requires_confirmation' => false,
                    'message' => 'Dit project is al geautoriseerd.',
                    'project' => $this->projectState($lockedProject),
                ];
            }

            if ((int) $lockedProject->locked !== 0) {
                return [
                    'authorized' => false,
                    'blocked' => true,
                    'requires_confirmation' => false,
                    'message' => 'Dit project is geblokkeerd voor autorisatie.',
                    'project' => $this->projectState($lockedProject),
                ];
            }

            $assessment = $this->checkProjectAuthorization->handle($lockedProject);

            if ($assessment['can_authorize']) {
                $lockedProject->sampleAnalyses()->update(['storedResult' => null]);

                foreach ($analyses as $analysis) {
                    try {
                        $this->calculateAnalysisResult->handle($analysis);
                    } catch (Throwable $exception) {
                        report($exception);

                        throw ValidationException::withMessages([
                            'authorization' => "Project niet geautoriseerd: het eindresultaat van analyse {$analysis->id} kon niet worden berekend.",
                        ]);
                    }
                }

                $assessment = $this->checkProjectAuthorization->handle($lockedProject);
            }

            if (! $assessment['can_authorize']) {
                return [
                    'authorized' => false,
                    'blocked' => true,
                    'requires_confirmation' => false,
                    'assessment' => $assessment,
                    'project' => $this->projectState($lockedProject),
                ];
            }

            if ($assessment['requires_confirmation'] && ! $overrideIncomplete) {
                return [
                    'authorized' => false,
                    'blocked' => false,
                    'requires_confirmation' => true,
                    'assessment' => $assessment,
                    'project' => $this->projectState($lockedProject),
                ];
            }

            $authorizedAt = now()->timestamp;
            $from = (string) $lockedProject->auth_status;
            $lockedProject->auth_on = $authorizedAt;
            $lockedProject->auth_status = 1;
            $lockedProject->auth_by = $userId;
            $lockedProject->last_edit = $authorizedAt;
            $lockedProject->save();
            $lockedProject->sampleAnalyses()->update([
                'auth_status' => 1,
                'auth_by' => $userId,
            ]);

            DB::table('changetracker')->insert([
                'user_id' => $userId,
                'timestamp' => (string) $authorizedAt,
                'type' => '9',
                'project' => $lockedProject->id,
                'event' => 'Project authorisatie gewijzigd',
                'from' => $from,
                'to' => '1',
            ]);

            return [
                'authorized' => true,
                'blocked' => false,
                'requires_confirmation' => false,
                'assessment' => $assessment,
                'project' => $this->projectState($lockedProject),
            ];
        });

        if (! $result['authorized']) {
            return $result;
        }

        try {
            $response = $this->clientPortal->projectAuthorized($project->id, $quiet);
            $result['portal_sync_failed'] = ! $response->successful();
        } catch (Throwable $exception) {
            report($exception);
            $result['portal_sync_failed'] = true;
        }

        return $result;
    }

    /** @return array<string, mixed> */
    private function projectState(Project $project): array
    {
        return $project->only([
            'id', 'auth_status', 'auth_by', 'auth_on', 'revision', 'rap_stat', 'last_edit', 'locked', 'lock_message',
        ]);
    }
}
