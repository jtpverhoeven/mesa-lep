<?php

namespace App\Actions\Reports;

use App\Models\Export;
use App\Models\Project;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;

class GetProjectReportHistory
{
    /** @return list<array<string, mixed>> */
    public function handle(Project $project): array
    {
        return $project->exports()->orderByDesc('transaction')->orderBy('id')->get()
            ->groupBy(fn (Export $export): string => $export->transaction === null ? 'legacy-'.$export->id : (string) $export->transaction)
            ->map(function (Collection $exports) use ($project): array {
                $last = $exports->last();

                return [
                    'id' => $last->id,
                    'transaction' => $last->transaction,
                    'send_url' => $last->transaction === null ? null : route('reports.send.show', $last->transaction),
                    'date' => is_numeric($last->date) ? Carbon::createFromTimestamp((int) $last->date, 'Europe/Amsterdam')->format('d-m-Y') : $last->date,
                    'type' => match ((int) $last->type) {
                        1 => 'Rapport', 2 => 'Voorlopig rapport', 3 => 'Rapport per monster', default => 'Onbekend',
                    },
                    'revision' => $last->revision,
                    'content' => $exports->map(fn (Export $export): array => [
                        'id' => $export->id,
                        'reference' => ($export->true_reference ?? $project->reference).'.'.$export->revision.'.'.$export->print_version,
                        'url' => route('projects.reports.show', [$project, $export]),
                    ])->all(),
                ];
            })->values()->all();
    }
}
