<?php

namespace App\Actions\Projects;

use App\Models\Project;
use App\Models\Sample;
use App\Models\SampleAnalysis;
use Illuminate\Database\Eloquent\Collection;

class CheckProjectAuthorization
{
    /**
     * @return array{
     *     project_complete: bool,
     *     results_complete: bool,
     *     details_complete: bool,
     *     innoc_date_valid: bool,
     *     can_authorize: bool,
     *     requires_confirmation: bool,
     *     checks: list<array{key: string, passed: bool, severity: string, message: string}>
     * }
     */
    public function handle(Project $project): array
    {
        $samples = $project->samples()
            ->with('analyses:id,sample,is_ready')
            ->get(['id', 'project', 'custom_fields', 'sample_innoculated']);

        $resultsComplete = $this->resultsComplete($samples);
        $detailsComplete = $this->detailsComplete($samples);
        $innocDateValid = $this->innocDateValid($samples);
        $checks = [
            [
                'key' => 'results_complete',
                'passed' => $resultsComplete,
                'severity' => 'warning',
                'message' => 'Niet alle resultaten zijn gereed.',
            ],
            [
                'key' => 'details_complete',
                'passed' => $detailsComplete,
                'severity' => 'warning',
                'message' => 'Niet alle monsterdetails zijn ingevuld.',
            ],
            [
                'key' => 'innoc_date_valid',
                'passed' => $innocDateValid,
                'severity' => 'blocking',
                'message' => 'Er ontbreken inzetdatums of er zijn verschillende inzetdatums gevonden binnen een project.',
            ],
        ];

        $projectComplete = true;
        $canAuthorize = true;
        $requiresConfirmation = false;

        foreach ($checks as $check) {
            if ($check['passed']) {
                continue;
            }

            $projectComplete = false;

            if ($check['severity'] === 'blocking') {
                $canAuthorize = false;
            } else {
                $requiresConfirmation = true;
            }
        }

        return [
            'project_complete' => $projectComplete,
            'results_complete' => $resultsComplete,
            'details_complete' => $detailsComplete,
            'innoc_date_valid' => $innocDateValid,
            'can_authorize' => $canAuthorize,
            'requires_confirmation' => $canAuthorize && $requiresConfirmation,
            'checks' => $checks,
        ];
    }

    /**
     * @param  Collection<int, Sample>  $samples
     */
    private function resultsComplete(Collection $samples): bool
    {
        foreach ($samples as $sample) {
            if ($sample->analyses->isEmpty()) {
                return false;
            }

            if ($sample->analyses->contains(fn (SampleAnalysis $analysis): bool => ! $analysis->is_ready)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param  Collection<int, Sample>  $samples
     */
    private function detailsComplete(Collection $samples): bool
    {
        foreach ($samples as $sample) {
            $customFields = json_decode($sample->custom_fields ?: '{}', true);

            if (is_array($customFields) && array_key_exists('details', $customFields) && $customFields['details'] == '') {
                return false;
            }
        }

        return true;
    }

    /**
     * @param  Collection<int, Sample>  $samples
     */
    private function innocDateValid(Collection $samples): bool
    {
        $inoculationDays = [];

        foreach ($samples as $sample) {
            $inoculationDate = $sample->sample_innoculated;

            if (empty($inoculationDate)) {
                return false;
            }

            $inoculationDays[date('Ymd', (int) $inoculationDate)] = true;

            if (count($inoculationDays) > 1) {
                return false;
            }
        }

        return true;
    }
}
