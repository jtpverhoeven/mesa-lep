<?php

namespace App\Actions\Reports;

use App\Actions\Projects\ResolveProjectReference;
use App\Models\Assay;
use App\Models\DocgenTemplate;
use App\Models\Export;
use App\Models\Project;
use App\Support\AccountImages;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use UnexpectedValueException;

class GetProjectReportOptions
{
    public function __construct(private GetReportingPreferences $preferences, private ResolveProjectReference $reference) {}

    /** @return array<string, mixed> */
    public function handle(Project $project, bool $temporary = false): array
    {
        $project->loadMissing(['client', 'authorizedBy', 'samples.analyses.assayRecord']);
        if (blank($project->reference)) {
            $project->reference = $this->reference->handle($project);
        }
        $assays = $this->assays($project);
        $candidates = $this->candidates($assays->modelKeys(), $temporary)->load('footers');
        $templates = $candidates->filter(fn (DocgenTemplate $template): bool => $this->missingAssays($template, $assays)->isEmpty());
        $preferences = $this->preferences->handle($project);
        // Temporary reports are always one combined PDF, whatever the client's portal preference says.
        $needsBulk = (int) (($temporary || $preferences['pdfTypeSelect'] === 1) && $preferences['noSamples'] > 1);
        $preferred = $templates->first(fn (DocgenTemplate $template): bool => $template->isSupported()
            && $template->language() === $preferences['language']
            && in_array($template->acceptsBulk(), [$needsBulk, 2], true));
        $signature = $project->authorizedBy === null ? null : AccountImages::signaturePath($project->authorizedBy, 'nl');

        return [
            'project' => $project,
            'samples' => $project->samples->sortBy('id')->values(),
            'assays' => $assays,
            'templates' => $templates,
            'preferences' => $preferences,
            'preferredTemplate' => $preferred?->folder,
            'assayOrder' => $this->defaultAssayOrder($project),
            'templateAvailability' => $candidates->map(fn (DocgenTemplate $template): array => [
                'id' => $template->id,
                'name' => $template->name,
                'folder' => $template->folder,
                'supported' => $template->isSupported(),
                'language' => $template->language(),
                'acceptsBulk' => $template->acceptsBulk(),
                'missingAssays' => $this->missingAssays($template, $assays)->map(fn (Assay $assay): array => $assay->only(['id', 'name']))->values()->all(),
                'footerScopes' => $template->footers->where('lang', $template->language())
                    ->filter(fn ($footer): bool => trim($footer->data) !== '')->pluck('q')->unique()->values()->all(),
            ])->values()->all(),
            'signatureAvailable' => $signature !== null && Storage::disk('local')->exists($signature),
            'history' => $this->history($project),
        ];
    }

    /** @return Collection<int, Assay> */
    public function assays(Project $project): Collection
    {
        $project->loadMissing('samples.analyses.assayRecord');

        $analyses = $project->samples->sortBy('id')
            ->flatMap(fn ($sample) => $sample->analyses->pluck('assayRecord'))
            ->all();

        if (in_array(null, $analyses, true)) {
            throw new UnexpectedValueException("Project [{$project->id}] contains analyses without an assay.");
        }

        return new Collection(collect($analyses)->unique('id')->values()->all());
    }

    /** @return Collection<int, DocgenTemplate> */
    public function templates(Project $project, bool $temporary = false): Collection
    {
        $assayIds = $this->assays($project)->modelKeys();

        if ($assayIds === []) {
            return new Collection;
        }

        return $this->candidates($assayIds, $temporary)->filter(fn (DocgenTemplate $template): bool => collect($assayIds)
            ->diff($template->associations->pluck('assay_id'))->isEmpty())->values();
    }

    /** @param list<int> $assayIds
     * @return Collection<int, DocgenTemplate>
     */
    private function candidates(array $assayIds, bool $temporary): Collection
    {
        return DocgenTemplate::query()->where('active', 1)->inDisplayOrder()
            ->when($temporary, fn ($query) => $query->allowedForTemporaryReports())
            ->with(['associations' => fn ($query) => $query->whereIn('assay_id', $assayIds)])
            ->get();
    }

    /** @param Collection<int, Assay> $assays
     * @return Collection<int, Assay>
     */
    private function missingAssays(DocgenTemplate $template, Collection $assays): Collection
    {
        return $assays->whereNotIn('id', $template->associations->pluck('assay_id'));
    }

    /** @return list<int> */
    public function defaultAssayOrder(Project $project): array
    {
        $assays = $this->assays($project);
        $sampleOrders = [];

        foreach ($project->samples->sortBy('id') as $sample) {
            $positions = [];
            $offset = 0;

            foreach ($sample->analyses as $analysis) {
                $position = (int) $analysis->project_order;

                if (isset($positions[$position])) {
                    $position += ++$offset;
                }

                $positions[$position] = (int) $analysis->assay_base;
            }

            $sampleOrders[] = array_values($positions);
        }

        $order = [];
        $length = max(array_map('count', $sampleOrders) ?: [0]);

        // Legacy interleaves sample positions instead of appending each sample's entire assay list.
        for ($position = 0; $position < $length; $position++) {
            foreach ($sampleOrders as $sampleOrder) {
                if (isset($sampleOrder[$position]) && ! in_array($sampleOrder[$position], $order, true)) {
                    $order[] = $sampleOrder[$position];
                }
            }
        }

        return array_values(array_unique([...$order, ...$assays->modelKeys()]));
    }

    /** @return list<array<string, mixed>> */
    private function history(Project $project): array
    {
        return $project->exports()->orderByDesc('transaction')->orderBy('id')->get()
            ->groupBy(fn (Export $export): string => $export->transaction === null ? 'legacy-'.$export->id : (string) $export->transaction)
            ->map(function (Collection $exports) use ($project): array {
                $last = $exports->last();

                return [
                    'id' => $last->id,
                    'transaction' => $last->transaction,
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
