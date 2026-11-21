<?php

namespace App\Actions\AuditTrail;

use App\Actions\Projects\GetProjectInfo;
use App\AssuranceForms\AssuranceValueAssessment;
use App\Models\Client;
use App\Models\Export;
use App\Models\FileHistory;
use App\Models\Note;
use App\Models\Project;
use App\Models\Sample;
use App\Models\SampleFile;
use App\Support\LegacyTimestamp;
use App\Support\NoteHtml;
use App\Support\UserNames;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * What happened to a project: when and how it came in, who authorized and reported it, its notes, the reports it
 * produced, what was sent to the client and every recorded change. New in the Laravel transplant (no legacy equivalent).
 */
class BuildProjectAuditTrail
{
    private const SEND_CHANGE_TYPE = '21';

    public function __construct(
        private GetProjectInfo $projectInfo,
        private ListAuditRevisions $revisions,
        private UserNames $users,
    ) {}

    /** @return array<string, mixed> */
    public function handle(Project $project): array
    {
        $project->loadMissing('client:id,name');
        $client = $project->getRelation('client');
        $samples = $project->samples()->with(['reportNote', 'internalNotes.user'])->withCount('analyses')->orderBy('id')->get();
        $info = $this->projectInfo->handle($project);
        $revisions = $this->revisions->handle('project', $project->id);

        return [
            'id' => $project->id,
            'overview' => $this->overview($project, $client, $info, $samples),
            'samples' => $samples->map(fn (Sample $sample): array => [
                'id' => $sample->id,
                'barcode' => $sample->barcode,
                'description' => $sample->description,
                'type' => $sample->sample_type,
                'source' => $sample->sourceLabel(),
                'registered_on' => LegacyTimestamp::format($sample->date_registered, 'd-m-Y H:i'),
                'registered_by' => $this->users->name($sample->registered_by),
                'inoculated_on' => LegacyTimestamp::format($sample->sample_innoculated, 'd-m-Y H:i'),
                'analyses' => (int) $sample->analyses_count,
            ])->all(),
            'notes' => $this->notes($project, $samples),
            'reports' => $this->reports($project),
            'sendings' => collect($revisions)->where('type_code', self::SEND_CHANGE_TYPE)->values()->all(),
            'files' => $this->files($project),
            'revisions' => $revisions,
        ];
    }

    /**
     * @param  array<string, mixed>  $info
     * @param  Collection<int, Sample>  $samples
     * @return array<string, mixed>
     */
    private function overview(Project $project, ?Client $client, array $info, $samples): array
    {
        return [
            'reference' => $project->reference,
            'name' => $project->project_name,
            'client' => $client === null ? null : ['id' => $client->id, 'name' => $client->name],
            'created_on' => LegacyTimestamp::format($project->project_date, 'd-m-Y H:i'),
            'created_by' => $project->added_by === null ? null : $this->users->name($project->added_by),
            'sources' => $samples->countBy(fn (Sample $sample): string => $sample->sourceLabel())
                ->map(fn (int $count, string $source): array => ['source' => $source, 'count' => $count])->values()->all(),
            'portal_id' => $project->portal_id,
            'status' => $info['project']['status'],
            'status_tone' => $info['project']['status_tone'],
            'revision' => $project->revision,
            'print_version' => $project->print_version,
            'authorized_on' => $project->isAuthorized() ? LegacyTimestamp::format($project->auth_on, 'd-m-Y H:i') : null,
            'authorized_by' => $project->isAuthorized() ? $this->users->name($project->auth_by) : null,
            'reported_on' => (int) $project->rap_stat === 1 ? LegacyTimestamp::format($project->rap_on, 'd-m-Y H:i') : null,
            'reported_by' => (int) $project->rap_stat === 1 ? $this->users->name($project->rap_by) : null,
            'blocked' => $project->isAuthorizationBlocked() ? ['by' => $this->users->name($project->locked_by), 'message' => (string) $project->lock_message] : null,
            'billed' => $project->isBilled(),
            'inoculation_date' => $info['inoculation_date'],
            'expected_date' => $info['expected_date'],
            'became_ready_on' => LegacyTimestamp::format($project->became_ready_on, 'd-m-Y H:i'),
            'fields' => $info['fields'],
            'extra_fields' => $info['extra_fields'],
        ];
    }

    /**
     * @param  Collection<int, Sample>  $samples
     * @return array{report: list<array<string, mixed>>, internal: list<array<string, mixed>>}
     */
    private function notes(Project $project, $samples): array
    {
        $report = collect([['label' => 'Project', 'note' => $project->reportNote]])
            ->concat($samples->map(fn (Sample $sample): array => ['label' => 'Monster '.$sample->barcode, 'note' => $sample->reportNote]))
            ->filter(fn (array $row): bool => $row['note'] instanceof Note && ! NoteHtml::isBlank($row['note']->body))
            ->map(fn (array $row): array => [
                'label' => $row['label'],
                'text' => NoteHtml::toPlainText($row['note']->body),
                'updated_at' => $this->noteDate($row['note']->updated_at),
            ]);
        $internal = $samples->flatMap(fn (Sample $sample) => $sample->internalNotes->map(fn (Note $note): array => [
            'barcode' => $sample->barcode,
            'author' => $note->user?->name ?? 'Legacy',
            'created_at' => $this->noteDate($note->created_at),
            'text' => NoteHtml::toPlainText($note->body),
        ]));

        return ['report' => $report->values()->all(), 'internal' => $internal->values()->all()];
    }

    private function noteDate(?Carbon $date): ?string
    {
        return $date?->copy()->setTimezone(AssuranceValueAssessment::TIMEZONE)->format('d-m-Y H:i');
    }

    /** @return list<array<string, mixed>> */
    private function reports(Project $project): array
    {
        return $project->exports()->orderBy('id')->get()->map(fn (Export $export): array => [
            'id' => $export->id,
            'transaction' => $export->transaction,
            'date' => is_numeric($export->date) ? LegacyTimestamp::format($export->date, 'd-m-Y H:i') : $export->date,
            'type' => $export->typeLabel(),
            'reference' => ($export->true_reference ?? $project->reference).'.'.$export->revision.'.'.$export->print_version,
            'revision' => $export->revision,
            'temporary' => (bool) $export->temporary,
            'was_authorized' => (bool) $export->was_authorized,
            'sample' => $export->sample_id,
        ])->all();
    }

    /** @return list<array<string, mixed>> */
    private function files(Project $project): array
    {
        return $project->sampleFiles()->with(['sample:id,barcode', 'histories.sender:id,name'])->get()
            ->map(fn (SampleFile $file): array => [
                'id' => $file->id,
                'barcode' => $file->sample?->barcode,
                'name' => $file->original_name,
                'uploaded_at' => $this->noteDate($file->created_at),
                'visible_for_client' => (bool) $file->visible_for_client,
                'deliveries' => $file->histories->map(fn (FileHistory $history): array => [
                    'sent_on' => $this->noteDate($history->sent_on),
                    'sent_by' => $history->sender?->name ?? 'Onbekend',
                    'method' => $history->sent_method,
                    'recipients' => trim(implode(', ', array_filter([
                        is_array($history->sent_to) ? (string) ($history->sent_to['groups'] ?? '') : '',
                        is_array($history->sent_to) ? (string) ($history->sent_to['extra'] ?? '') : '',
                    ]))),
                ])->all(),
            ])->all();
    }
}
