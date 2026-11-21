<?php

namespace App\Actions\AuditTrail;

use App\AssuranceForms\AssuranceValueAssessment;
use App\AssuranceForms\AssuranceValueRepository;
use App\AssuranceForms\SampleAssuranceValues;
use App\Models\Client;
use App\Models\Note;
use App\Models\Project;
use App\Models\Sample;
use App\Models\SampleProcedure;
use App\Support\LegacyTimestamp;
use App\Support\NoteHtml;
use App\Support\UserNames;

/**
 * Port of legacy samplesController::sampleAuditTrail. The general part is always available; the analysis part
 * needs the sample to be inoculated and the assurance form of that day to exist.
 */
class BuildSampleAuditTrail
{
    public function __construct(
        private AssuranceValueAssessment $assessment,
        private AssuranceValueRepository $assuranceForms,
        private BuildSampleAnalysisAuditTrail $analysis,
        private ListAuditRevisions $revisions,
        private UserNames $users,
    ) {}

    /** @return array<string, mixed> */
    public function handle(Sample $sample): array
    {
        $sample->load(['client', 'project.reportNote', 'reportNote', 'internalNotes.user']);
        $project = $sample->getRelation('project');
        $form = $this->assuranceForms->formForSample($sample);
        $inoculated = LegacyTimestamp::format($sample->sample_innoculated, 'd-m-Y H:i');
        $values = $form === null ? null : new SampleAssuranceValues($this->assessment, $form, $sample->id);

        return [
            'id' => $sample->id,
            'barcode' => $sample->barcode,
            'client' => $this->client($sample->getRelation('client')),
            'project' => $project === null ? null : $this->project($project, $sample),
            'general' => $this->general($sample, $project, $inoculated),
            'assurance' => [
                'available' => $form !== null,
                'reason' => match (true) {
                    $form !== null => null,
                    $inoculated === null => 'not_inoculated',
                    default => 'no_assurance_form',
                },
                'form' => $form === null ? null : [
                    'id' => (int) $form->id,
                    'date' => $values->formDate()->format('d-m-Y'),
                    'form_date' => $values->formDate()->format('Y-m-d'),
                    'is_complete' => (bool) $form->is_complete,
                ],
            ],
            'analysis' => $values === null ? null : $this->analysis->handle($sample, $values),
            'revisions' => [
                'project' => $project === null ? [] : $this->revisions->handle('project', $project->id),
                'sample' => $this->revisions->handle('sample', $sample->id),
                'assurance_form' => $form === null ? [] : $this->revisions->handle('assurance-form', $form->id),
            ],
        ];
    }

    /** @return array<string, mixed>|null */
    private function client(?Client $client): ?array
    {
        if ($client === null) {
            return null;
        }

        return [
            'id' => $client->id,
            'name' => $client->name,
            'contact' => trim(preg_replace('/\s+/', ' ', implode(' ', [$client->title, $client->fname, $client->mname, $client->lname])) ?? ''),
            'street' => trim($client->street_name.' '.$client->street_number),
            'place' => $client->place,
        ];
    }

    /** @return array<string, mixed> */
    private function project(Project $project, Sample $sample): array
    {
        return [
            'id' => $project->id,
            'reference' => $project->reference,
            'name' => $project->project_name,
            'number_of_samples' => $project->samples()->count(),
            'revision' => $project->revision,
            'authorized_on' => $project->auth_on === null ? null : LegacyTimestamp::format($project->auth_on),
            'authorized_by' => $project->auth_on === null ? null : $this->users->name($project->auth_by),
            'reported_on' => $project->rap_on === null ? null : LegacyTimestamp::format($project->rap_on),
            'reported_by' => $project->rap_on === null ? null : $this->users->name($project->rap_by),
            'notes' => array_values(array_filter([
                $this->reportNote('Project', $project->reportNote),
                $this->reportNote('Monster '.$sample->barcode, $sample->reportNote),
            ])),
        ];
    }

    /** @return array{label: string, text: string}|null */
    private function reportNote(string $label, ?Note $note): ?array
    {
        $text = $note === null ? '' : NoteHtml::toPlainText($note->body);

        return $text === '' ? null : ['label' => $label, 'text' => $text];
    }

    /** @return array<string, mixed> */
    private function general(Sample $sample, ?Project $project, ?string $inoculated): array
    {
        $projectFields = $this->json($project?->custom_fields);
        $sampleFields = $this->json($sample->custom_fields);
        $procedure = SampleProcedure::query()->find($this->samplingMethodId($sample, $project));

        return [
            'date_sampling' => (string) ($projectFields['project_monster'] ?? ''),
            'date_receive' => (string) ($projectFields['project_ontvangst'] ?? ''),
            'time_receive' => (string) ($projectFields['project_tijd_ontvangst'] ?? ''),
            'inoculated' => $inoculated,
            'sampling_by' => (string) ($this->json($procedure?->fields)['door'] ?? ''),
            'sampling_method' => (string) $procedure?->name,
            'description' => $sample->description,
            'details' => (string) ($sampleFields['details'] ?? ''),
            'stored_in' => $sample->stored_in,
            'diluted_at' => $sample->diluted_at,
            'tht_code' => $sample->tht_code,
            'notes' => $sample->internalNotes->map(fn (Note $note): array => [
                'author' => $note->user?->name ?? 'Legacy',
                'created_at' => $note->created_at?->copy()->setTimezone(AssuranceValueAssessment::TIMEZONE)->format('d-m-Y H:i'),
                'text' => NoteHtml::toPlainText($note->body),
            ])->all(),
        ];
    }

    /** Legionella samples take the method from the project, falling back to the sample. */
    private function samplingMethodId(Sample $sample, ?Project $project): mixed
    {
        if ($sample->sample_type === 'L') {
            $projectMethod = $this->json($project?->project_extra)['sample_method'] ?? null;

            return $projectMethod ?: $sample->sampling_method;
        }

        return $sample->sampling_method;
    }

    /** @return array<string, mixed> */
    private function json(?string $value): array
    {
        $decoded = json_decode($value ?: '{}', true);

        return is_array($decoded) ? $decoded : [];
    }
}
