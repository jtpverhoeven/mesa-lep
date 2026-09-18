<?php

namespace App\Actions\Projects;

use App\Models\Project;
use App\Models\Sample;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

class SearchProjects
{
    public function handle(string $mode, string $query): array
    {
        $matchingSample = null;
        $projects = Project::query()
            ->with('client:id,name')
            ->withCount('samples')
            ->withMin('samples', 'sample_innoculated')
            ->withMax('samples', 'sample_innoculated');

        if ($mode === 'barcode') {
            $barcode = explode('.', $query, 2)[0];
            $matchingSample = Sample::query()->where('barcode', $barcode)->first(['id', 'project']);
            $projects->whereKey($matchingSample?->project ?? 0);
        } else {
            $projects->where(function (Builder $projects) use ($query): void {
                $projects->where('reference', 'like', "%{$query}%")
                    ->orWhere('project_name', 'like', "%{$query}%");
            });
        }

        return [
            'projects' => $projects
                ->orderByDesc('project_date')
                ->orderByDesc('id')
                ->get()
                ->map(fn (Project $project): array => $this->summary($project))
                ->all(),
            'selected_project_id' => $matchingSample?->project,
            'selected_sample_id' => $matchingSample?->id,
        ];
    }

    private function summary(Project $project): array
    {
        $fields = json_decode($project->custom_fields ?: '{}', true);
        $fields = is_array($fields) ? $fields : [];

        return [
            ...$project->only(['id', 'reference', 'project_name', 'project_date', 'revision', 'auth_status', 'is_ready', 'started']),
            'client_name' => $project->getRelation('client')?->name,
            'samples_count' => $project->samples_count,
            'sampling_date' => $fields['project_monster'] ?? null,
            'received_date' => $fields['project_ontvangst'] ?? null,
            'inoculation_date' => $this->inoculationDate(
                $project->samples_min_sample_innoculated,
                $project->samples_max_sample_innoculated,
            ),
            'status' => $this->status($project),
        ];
    }

    private function inoculationDate(?string $first, ?string $last): string
    {
        if (! is_numeric($first)) {
            return 'Nog niet ingezet';
        }

        $firstDate = Carbon::createFromTimestamp((int) $first);
        if (! is_numeric($last) || $first === $last || $firstDate->isSameDay(Carbon::createFromTimestamp((int) $last))) {
            return $firstDate->format('d-m-Y H:i');
        }

        return $firstDate->format('d-m-Y H:i').' / '.Carbon::createFromTimestamp((int) $last)->format('d-m-Y H:i');
    }

    private function status(Project $project): string
    {
        return match (true) {
            (bool) $project->auth_status => 'Geautoriseerd',
            (bool) $project->is_ready => 'Gereed',
            (bool) $project->started => 'Lopend',
            default => 'Ontvangen',
        };
    }
}
