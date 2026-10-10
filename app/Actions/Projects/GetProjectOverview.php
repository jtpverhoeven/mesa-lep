<?php

namespace App\Actions\Projects;

use App\Models\Project;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use InvalidArgumentException;

class GetProjectOverview
{
    public const STATUSES = [
        'received' => 'Ontvangen',
        'running' => 'Lopend',
        'completed' => 'Afgerond',
        'authorized' => 'Geautoriseerd',
        'reported' => 'PDF gegenereerd',
        'blocked' => 'Geblokkeerd',
    ];

    /**
     * Legacy memberships overlap: received only checks started, and blocking does not replace lifecycle views.
     *
     * @return LengthAwarePaginator<int, array<string, mixed>>
     */
    public function handle(string $status, int $page = 1): LengthAwarePaginator
    {
        [$filters, $sort, $direction] = match ($status) {
            'received' => [['started' => 0], 'project_date', 'asc'],
            'running' => [['is_ready' => 0, 'started' => 1, 'auth_status' => 0], 'predicted_end', 'asc'],
            'completed' => [['auth_status' => 0, 'is_ready' => 1, 'started' => 1], 'became_ready_on', 'asc'],
            'authorized' => [['auth_status' => 1, 'rap_stat' => 0, 'started' => 1], 'auth_on', 'asc'],
            'reported' => [['auth_status' => 1, 'rap_stat' => 1, 'started' => 1], 'rap_on', 'desc'],
            'blocked' => [['locked' => 1, 'auth_status' => 0], 'predicted_end', 'asc'],
            default => throw new InvalidArgumentException('Unknown project overview status.'),
        };

        $projects = Project::query()
            ->select([
                'id', 'reference', 'client', 'project_name', 'project_date', 'auth_status',
                'auth_on', 'rap_stat', 'rap_by', 'rap_on', 'became_ready_on', 'predicted_end',
                'special_type', 'locked', 'lock_message', 'custom_fields', 'print_info',
            ])
            ->with(['client:id,name', 'reportedBy:id,name', 'reportedBy.profile:id,user_id,first_name,last_name'])
            ->withCount('samples')
            ->where($filters)
            ->orderBy($sort, $direction)
            ->orderBy('id');

        if ($status === 'received') {
            $projects->withCount([
                'samples as unassigned_samples_count' => fn (Builder $samples): Builder => $samples
                    ->whereDoesntHave('analyses', fn (Builder $analyses): Builder => $analyses
                        ->whereColumn('sampleanalysis.project', 'samples.project')),
            ]);
        }

        return $projects->paginate(50, ['*'], 'page', $page)
            ->through(fn (Project $project): array => $this->summary($project, $status));
    }

    /** @return array<string, mixed> */
    private function summary(Project $project, string $status): array
    {
        $fields = json_decode($project->custom_fields ?: '{}', true);
        $printInfo = json_decode($project->print_info ?: '{}', true);
        $profile = $project->reportedBy?->profile;
        $reporter = trim(($profile?->first_name ?? '').' '.($profile?->last_name ?? ''));

        return [
            ...$project->only([
                'id', 'reference', 'project_date', 'special_type', 'predicted_end',
                'became_ready_on', 'auth_on', 'rap_on', 'locked', 'lock_message',
            ]),
            'url' => route('projects.search', ['project' => $project->id]),
            'client_name' => $project->getRelation('client')?->name ?? '?',
            'client_reference' => trim($project->reference ?? '') === trim($project->project_name ?? '')
                ? '' : $project->project_name,
            'samples_count' => $project->samples_count,
            'all_samples_have_analysis' => $status === 'received'
                && $project->samples_count > 0 && $project->unassigned_samples_count === 0,
            'received_date' => is_array($fields) ? ($fields['project_ontvangst'] ?? null) : null,
            'last_print_name' => is_array($printInfo) ? ($printInfo['last_print_name'] ?? '') : '',
            'print_times' => is_array($printInfo) ? ($printInfo['print_times'] ?? null) : null,
            'reported_by' => $reporter ?: ($project->reportedBy?->name ?? '?'),
            'status' => $this->status($project, $status),
            'progress' => $this->progress($project),
            'deadline_tone' => $status === 'running' ? $this->deadlineTone($project) : null,
        ];
    }

    private function status(Project $project, string $status): string
    {
        if ((int) $project->auth_status === 1) {
            return self::STATUSES[(int) $project->rap_stat === 1 ? 'reported' : 'authorized'];
        }

        return $status === 'running' && (int) $project->locked === 1
            ? 'Lopend (Geblokkeerd)' : self::STATUSES[$status];
    }

    private function progress(Project $project): int
    {
        if ((int) $project->auth_status === 1) {
            return 100;
        }

        if (! is_numeric($project->predicted_end) || ! is_numeric($project->project_date)
            || (int) $project->predicted_end === -1) {
            return 0;
        }

        $distance = (int) $project->predicted_end - (int) $project->project_date;

        return $distance === 0 ? 100 : (int) min(100, ceil(
            (1 - (((int) $project->predicted_end - now()->timestamp) / $distance)) * 100,
        ));
    }

    private function deadlineTone(Project $project): ?string
    {
        if (! is_numeric($project->predicted_end) || (int) $project->predicted_end === -1) {
            return null;
        }

        $day = Carbon::createFromTimestamp((int) $project->predicted_end, 'Europe/Amsterdam')->startOfDay();
        $today = now('Europe/Amsterdam')->startOfDay();
        if ($day->greaterThanOrEqualTo($today)) {
            return null;
        }

        $workingDays = 0;
        while ($day->lessThan($today)) {
            if (! $day->isWeekend() && ! $this->isHoliday($day)) {
                $workingDays++;
                if ($workingDays > 3) {
                    return 'danger';
                }
            }
            $day->addDay();
        }

        return 'warning';
    }

    private function isHoliday(Carbon $day): bool
    {
        $easter = Carbon::create($day->year, 3, 21, 0, 0, 0, 'Europe/Amsterdam')->addDays(easter_days($day->year));
        $kingsDay = Carbon::create($day->year, 4, 27, 0, 0, 0, 'Europe/Amsterdam');
        if ($kingsDay->isSunday()) {
            $kingsDay->subDay();
        }

        $holidays = [
            '01-01', '12-25', '12-26', $kingsDay->format('m-d'),
            $easter->format('m-d'), $easter->copy()->addDay()->format('m-d'),
            $easter->copy()->addDays(39)->format('m-d'),
            $easter->copy()->addDays(49)->format('m-d'),
            $easter->copy()->addDays(50)->format('m-d'),
        ];

        return in_array($day->format('m-d'), $holidays, true)
            || ($day->year % 5 === 0 && $day->format('m-d') === '05-05');
    }
}
