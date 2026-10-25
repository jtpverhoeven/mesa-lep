<?php

namespace App\Actions\Reports;

use App\ClientPortal\ClientPortalService;
use App\Models\Project;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Log;
use UnexpectedValueException;

class GetReportingPreferences
{
    public function __construct(private ClientPortalService $portal) {}

    /** @return array<string, mixed> */
    public function handle(Project $project): array
    {
        $project->loadMissing('client');
        $defaults = [
            'showViolation' => (int) ($project->getRelation('client')?->trip_red ?? 0),
            'pdfTypeSelect' => (int) $project->special_type === 0 ? 0 : 1,
            'language' => 'nl',
            'foundSettings' => false,
            'activated_contact_groups' => [],
            'noSamples' => 5,
            'warning' => null,
            'portalProjectId' => $project->portal_id ? (int) $project->portal_id : null,
        ];

        if (! $project->portal_id) {
            return $defaults;
        }

        try {
            $response = $this->portal->getReportingPreferenceForProject($project->portal_id);

            if ($response->status() === 404) {
                return $defaults;
            }

            $response->throw();
            $payload = $response->json();

            if (! is_array($payload)) {
                throw new UnexpectedValueException('The portal returned invalid reporting preferences.');
            }

            $found = filter_var($payload['preferences']['foundSettings'] ?? null, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);

            if ((int) ($payload['status'] ?? 200) === 404) {
                return $defaults;
            }

            if ((int) ($payload['status'] ?? 200) !== 200) {
                throw new UnexpectedValueException('The portal could not resolve reporting preferences.');
            }

            if ($found === false && isset($payload['preferences']['foundSettings'])) {
                return $defaults;
            }

            $preferences = $payload['preferences'] ?? null;

            if (! is_array($preferences)
                || ! filter_var($preferences['foundSettings'] ?? false, FILTER_VALIDATE_BOOLEAN)
                || ! in_array($preferences['language'] ?? null, ['nl', 'en'], true)
                || ! is_string($preferences['reportOutputType'] ?? null)
                || filter_var($preferences['numberOfSamples'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0, 'max_range' => 5]]) === false
                || ! is_array($preferences['selectedContactLists'] ?? null)) {
                throw new UnexpectedValueException('The portal returned invalid reporting preferences.');
            }

            return [
                ...$defaults,
                'foundSettings' => true,
                'pdfTypeSelect' => $preferences['reportOutputType'] === 'bulk' ? 1 : 0,
                'language' => $preferences['language'],
                'noSamples' => (int) $preferences['numberOfSamples'] === 0 ? 5 : (int) $preferences['numberOfSamples'],
                'activated_contact_groups' => $preferences['selectedContactLists'],
            ];
        } catch (ConnectionException|RequestException|UnexpectedValueException $exception) {
            report($exception);
            Log::warning('Reporting preferences could not be loaded.', [
                'project_id' => $project->id,
                'portal_project_id' => $project->portal_id,
            ]);

            return [...$defaults, 'warning' => 'Klantvoorkeuren konden niet worden geladen. Controleer de rapportinstellingen handmatig.'];
        }
    }
}
