<?php

namespace App\Actions\Projects;

use App\Models\Project;
use App\Models\ProjectField;
use App\Models\SampleProcedure;
use App\Models\User;
use DateTimeImmutable;
use DateTimeZone;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

class GetProjectInfo
{
    public function handle(Project $project): array
    {
        $project->loadMissing([
            'client:id,name',
            'authorizedBy:id,name',
            'authorizedBy.profile:id,user_id,first_name,last_name,avatar_path',
            'reportedBy:id,name',
            'reportedBy.profile:id,user_id,first_name,last_name',
        ]);

        $values = json_decode($project->custom_fields ?: '{}', true);
        $values = is_array($values) ? $values : [];
        $definitions = ProjectField::query()->orderBy('position')->get(['name', 'alias']);
        $inoculations = $project->samples()
            ->whereNotNull('sample_innoculated')
            ->where('sample_innoculated', '!=', '')
            ->pluck('sample_innoculated')
            ->filter(fn (string $value): bool => is_numeric($value) && (int) $value > 0)
            ->map(fn (string $value): int => (int) $value);
        $first = $inoculations->min();
        $last = $inoculations->max();
        $firstDate = $first ? Carbon::createFromTimestamp($first, 'Europe/Amsterdam') : null;
        $lastDate = $last ? Carbon::createFromTimestamp($last, 'Europe/Amsterdam') : null;
        $twoDays = $firstDate && $lastDate && ! $firstDate->isSameDay($lastDate);

        $avatarPath = $project->authorizedBy?->profile?->avatar_path;

        return [
            'project' => [
                ...$project->only([
                    'id', 'client', 'reference', 'project_name', 'project_date', 'revision',
                    'print_version', 'portal_id', 'auth_status', 'auth_on', 'rap_stat', 'rap_on',
                    'is_ready', 'started', 'locked', 'lock_message', 'project_notes', 'predicted_end',
                ]),
                'client_name' => $project->getRelation('client')?->name,
                'status' => $this->status($project),
                'authorized_by' => (int) $project->auth_status === 1 ? [
                    'name' => $this->fullName($project->authorizedBy),
                    'avatar_url' => $avatarPath && Storage::disk('local')->exists($avatarPath)
                        ? route('projects.info.avatar', $project) : null,
                ] : null,
                'reported_by' => (int) $project->rap_stat === 1 ? $this->fullName($project->reportedBy) : null,
            ],
            'fields' => $definitions
                ->concat(collect(array_keys($values))->diff($definitions->pluck('name'))->map(
                    fn (string $name): object => (object) ['name' => $name, 'alias' => $name],
                ))
                ->map(fn ($field): array => [
                    'name' => $field->name,
                    'label' => $field->alias ?: $field->name,
                    'value' => $values[$field->name] ?? '',
                ])->values()->all(),
            'inoculation_date' => $twoDays
                ? $firstDate->format('d-m-Y H:i')."\n".$lastDate->format('d-m-Y H:i')
                : ($firstDate?->format('d-m-Y') ?? 'Nog niet ingezet'),
            'inoculation_time' => $twoDays ? 'Tweeledig' : ($firstDate?->format('H:i') ?? ''),
            'sampling_to_analysis' => $this->samplingToAnalysis($first, $values['project_monster'] ?? null, $values['project_bemonster_tijd'] ?? null),
            'expected_date' => (int) $project->predicted_end > 0
                ? Carbon::createFromTimestamp((int) $project->predicted_end, 'Europe/Amsterdam')->format('d-m-Y')
                : 'Nog niet bekend',
            'extra_fields' => $this->extraFields($project->project_extra),
            'portal_status_url' => route('projects.info.portal-status', $project),
            'order_form_url' => $project->portal_id !== null ? route('projects.info.order-form', $project) : null,
        ];
    }

    private function status(Project $project): string
    {
        if ((int) $project->auth_status !== 0) {
            return (int) $project->rap_stat === 1 ? 'PDF gegenereerd' : 'Geautoriseerd';
        }

        if ((int) $project->locked === 1) {
            return 'Geblokkeerd';
        }

        if ((int) $project->started === 0) {
            return 'Ontvangen';
        }

        return (int) $project->is_ready === 1 ? 'Afgerond' : 'Lopend';
    }

    private function fullName(?User $user): string
    {
        $name = trim(($user?->profile?->first_name ?? '').' '.($user?->profile?->last_name ?? ''));

        return $name ?: ($user?->name ?? 'Onbekend');
    }

    private function samplingToAnalysis(?int $inoculated, mixed $date, mixed $time): string
    {
        if ($inoculated === null) {
            return 'N.T.B. Geen inzet datum';
        }

        if (! is_string($date) || trim($date) === '' || mb_strtolower(trim($date)) === 'onbekend') {
            return 'N.T.B. Bemonster-datum onbekend';
        }

        if (! is_string($time) || trim($time) === '' || mb_strtolower(trim($time)) === 'onbekend') {
            return 'N.T.B. Bemonster-tijd onbekend';
        }

        $date = trim($date);
        $time = trim($time);
        $format = match (true) {
            (bool) preg_match('/^\d{2}-\d{2}-\d{4}$/', $date) => 'd-m-Y',
            (bool) preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) => 'Y-m-d',
            (bool) preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $date) => 'd/m/Y',
            default => null,
        };

        if ($format === null || ! preg_match('/^([01]\d|2[0-3])(?::[0-5]\d|[0-5]\d)$/', $time)) {
            return 'N.T.B. Datum/tijd veld(en) incorrect';
        }

        // Legacy termCalculation reads positions 2-3, so HH:mm always supplies zero minutes.
        $legacyTime = substr($time, 0, 2).($time[2] === ':' ? '00' : substr($time, 2, 2));
        $sampling = DateTimeImmutable::createFromFormat('!'.$format.' Hi', $date.' '.$legacyTime, new DateTimeZone('Europe/Amsterdam'));
        if ($sampling === false || $sampling->format($format.' Hi') !== $date.' '.$legacyTime) {
            return 'N.T.B. Datum/tijd veld(en) incorrect';
        }

        return (string) intdiv(abs($inoculated - $sampling->getTimestamp()), 3600).' uur';
    }

    private function extraFields(?string $json): array
    {
        $values = json_decode($json ?: '{}', true);
        if (! is_array($values)) {
            return [];
        }

        $fields = [];
        foreach ($values as $name => $value) {
            if ($name === 'sample_method') {
                $fields[] = ['label' => 'Bemonster. methode', 'value' => SampleProcedure::query()->whereKey($value)->value('name') ?? ''];
            } elseif ($name === 'client_reference') {
                $fields[] = ['label' => 'Referentie klant:', 'value' => $value];
            }
        }

        return $fields;
    }
}
