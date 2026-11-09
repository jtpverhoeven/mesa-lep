<?php

namespace App\Actions\Reports;

use App\Actions\EmailTemplates\BuildEmailTemplateSelector;
use App\Actions\Projects\GetEditability;
use App\Keyrings\KeyringClientConfig;
use App\Keyrings\KeyringType;
use App\Models\Export;
use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class ShowReportSender
{
    public function __construct(
        private GetReportTransaction $transaction,
        private BuildReportSenderFiles $files,
        private BuildEmailTemplateSelector $templates,
        private GetReportingPreferences $preferences,
        private GetReportContactGroups $contactGroups,
        private GetProjectReportHistory $history,
        private GetEditability $editability,
    ) {}

    /**
     * @return array<string, mixed>
     *
     * @throws ReportTransactionUnavailableException
     */
    public function handle(int $transaction, User $user): array
    {
        try {
            $exports = $this->transaction->handle($transaction);
        } catch (ModelNotFoundException) {
            throw ReportTransactionUnavailableException::notFound();
        }

        $first = $exports->first();
        $project = $first->projectRecord;

        if ($project === null) {
            throw ReportTransactionUnavailableException::notFound();
        }

        if ($exports->contains(fn (Export $export): bool => ! $export->pdfExists())) {
            throw ReportTransactionUnavailableException::missingPdf();
        }

        $unauthorized = $exports->contains(fn (Export $export): bool => (int) $export->was_authorized === 0);
        $language = $first->isEnglish() ? 'en' : 'nl';
        $selectors = [
            'reports' => $this->templates->handle('reports', null, $language),
            'report_with_files' => $this->templates->handle('report_with_files', null, $language),
            'for_files' => $this->templates->handle('for_files', null, $language),
        ];
        $config = [
            'transaction' => $transaction,
            'project' => $this->project($project),
            'clientName' => $first->clientRecord?->name,
            'reports' => $exports->map(fn (Export $export): array => [
                'id' => $export->id,
                'name' => $export->reportName(),
                'viewUrl' => route('projects.reports.show', [$export->getRawOriginal('project'), $export]),
                'downloadUrl' => route('reports.download', $export),
            ])->all(),
            'zipUrl' => $exports->count() > 1 ? route('reports.zip', $transaction) : null,
            'unauthorized' => $unauthorized,
            'defaultLanguage' => $language,
            'templates' => $selectors,
            'bcc' => $selectors['reports']['bcc'],
            'urls' => $this->urls($transaction, $project),
            'editability' => $this->editability->handle($project, KeyringType::Export, $project->id, $user, applyProjectRule: false),
            'keyrings' => KeyringClientConfig::for($user),
        ];

        if ($unauthorized) {
            return [...$config, 'versions' => $this->history->handle($project)];
        }

        $preferences = $this->preferences->handle($project);
        $groups = $this->contactGroups->handle((int) $first->getRawOriginal('client'), $preferences['activated_contact_groups']);

        return [
            ...$config,
            'files' => $this->files->rows($project, $exports),
            'contactGroups' => $groups['groups'],
            'contactGroupsError' => $groups['error'],
        ];
    }

    /** @return array<string, mixed> */
    private function project(Project $project): array
    {
        return [
            'id' => $project->id,
            'reference' => $project->reference,
            'name' => $project->project_name,
        ];
    }

    /** @return array<string, string> */
    private function urls(int $transaction, Project $project): array
    {
        return [
            'project' => route('projects.search', ['project' => $project->id]),
            'reportForm' => route('projects.reports.index', $project),
            'completed' => route('projects.overview.completed'),
            'authorized' => route('projects.overview.authorized'),
            'mixed' => route('reports.send.mixed', $transaction),
            'mail' => route('reports.send.mail', $transaction),
            'template' => route('send.template', ['emailTemplate' => '__TEMPLATE__', 'lang' => '__LANG__']),
            'history' => route('sample-files.history', ['sampleFile' => '__FILE__']),
        ];
    }
}
