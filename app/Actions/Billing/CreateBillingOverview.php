<?php

namespace App\Actions\Billing;

use App\Actions\Projects\FindProjectBySampleBarcode;
use App\Models\Client;
use App\Models\Project;
use App\Models\Sample;
use App\Models\SampleAnalysis;
use App\Support\Cvars;
use App\Support\LaboratoryCalendar;
use App\Support\ProjectInoculation;
use DateTimeImmutable;
use DateTimeZone;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\CarbonImmutable;
use Illuminate\Support\Collection;

/**
 * Port of legacy billingController::createBillingOverview ("Facturatie").
 */
class CreateBillingOverview
{
    public const STATUS_OK = 'ok';

    public const STATUS_NO_PROJECTS = 'no_projects';

    public const STATUS_BARCODE_NOT_FOUND = 'barcode_not_found';

    public const STATUS_BARCODE_FILTERED = 'barcode_filtered';

    public const STATUS_CLIENT_MISMATCH = 'client_mismatch';

    public const STATUS_INVALID_RANGE = 'invalid_project_range';

    /** Billing status filters: not yet billed, billed, or both. */
    public const BILLING_STATUSES = ['0', '1', 'all'];

    /** Legacy sample type rows in display order; "T" holds normal samples with a THT code. */
    private const SAMPLE_TYPES = ['S' => 'Normaal', 'T' => 'THT', 'R' => 'Rodac', 'L' => 'Legionella'];

    public function __construct(
        private FindProjectBySampleBarcode $findProject,
        private LaboratoryCalendar $calendar,
        private Cvars $cvars,
    ) {}

    /**
     * @param  list<int>  $projectIds  Previously selected projects, in selection order.
     * @param  string  $billingStatus  One of BILLING_STATUSES; selected projects outside it are dropped.
     * @param  int|null  $clientId  Range selection: client whose projects were inoculated between the dates.
     * @return array{
     *     status: string,
     *     projects: list<int>,
     *     client: array{id: int, name: ?string, debit_number: ?string, categories: list<string>}|null,
     *     project_rows: list<array<string, mixed>>,
     *     sample_types: list<array<string, mixed>>,
     *     articles: list<array{assay_base: int, name: ?string, article_code: string, total: int, conf_requested_count: int, positives: ?int}>,
     * }
     */
    public function handle(
        array $projectIds,
        ?string $barcode = null,
        string $billingStatus = '0',
        ?int $clientId = null,
        ?string $dateFrom = null,
        ?string $dateTo = null,
    ): array {
        $status = self::STATUS_OK;
        $billingStatus = in_array($billingStatus, self::BILLING_STATUSES, true) ? $billingStatus : '0';
        $projectIds = array_values(array_unique(array_filter(array_map('intval', $projectIds), fn (int $id): bool => $id > 0)));
        $dateFrom = trim((string) $dateFrom);
        $dateTo = trim((string) $dateTo);

        if ($clientId !== null || $dateFrom !== '' || $dateTo !== '') {
            $rangeProjects = $clientId !== null ? $this->projectsInRange($clientId, $dateFrom, $dateTo, $billingStatus) : null;

            if ($rangeProjects === null) {
                return $this->empty(self::STATUS_INVALID_RANGE);
            }

            $projectIds = array_values(array_unique([...$projectIds, ...$rangeProjects]));
        }

        if ($barcode !== null && $barcode !== '') {
            $scanned = ($projectId = $this->findProject->handle($barcode)) !== null ? Project::query()->find($projectId, ['id', 'billed']) : null;

            if ($scanned === null) {
                $status = self::STATUS_BARCODE_NOT_FOUND;
            } elseif ($scanned->matchesBillingStatus($billingStatus) && ! in_array($scanned->id, $projectIds, true)) {
                $projectIds[] = $scanned->id;
            } else {
                $status = self::STATUS_BARCODE_FILTERED;
            }
        }

        $projects = Project::query()->whereKey($projectIds)->withCount('samples')->get()->keyBy('id');
        $projectIds = array_values(array_filter(
            $projectIds,
            fn (int $id): bool => $projects->has($id) && $projects[$id]->matchesBillingStatus($billingStatus),
        ));
        $clients = collect($projectIds)->map(fn (int $id): int => (int) $projects[$id]->client)->unique();

        if ($clients->count() > 1 || ($clientId !== null && $clients->isNotEmpty() && ! $clients->contains($clientId))) {
            return $this->empty(self::STATUS_CLIENT_MISMATCH);
        }

        if ($projectIds === []) {
            return $this->empty($status === self::STATUS_OK ? self::STATUS_NO_PROJECTS : $status);
        }

        $samples = Sample::query()->whereIn('project', $projectIds)
            ->get(['id', 'project', 'sample_type', 'tht_code', 'sample_innoculated']);
        $automaticRodac = $this->automaticRodacSamples($samples);

        return [
            'status' => $status,
            'projects' => $projectIds,
            'client' => $this->client($clients->first()),
            'project_rows' => array_map(
                fn (int $id): array => $this->projectRow($projects[$id], $samples->where('project', $id), $automaticRodac),
                $projectIds,
            ),
            'sample_types' => $this->sampleTypes($samples, $automaticRodac, $projects),
            'articles' => $this->articles($projectIds),
        ];
    }

    /**
     * Port of legacy billingController::getBillingProjectsForRange: the client's projects with a sample
     * inoculated on or between both dates (d-m-Y or Y-m-d), newest project first; null when the range is invalid.
     *
     * @return list<int>|null
     */
    private function projectsInRange(int $clientId, string $dateFrom, string $dateTo, string $billingStatus): ?array
    {
        $from = $this->parseDate($dateFrom);
        $to = $this->parseDate($dateTo);

        if ($from === null || $to === null || $from->greaterThan($to)) {
            return null;
        }

        return Project::query()
            ->where('client', $clientId)
            ->billingStatus($billingStatus)
            ->whereIn('id', Sample::query()->select('project')->distinct()
                ->where(fn (Builder $samples) => $this->whereNumericString($samples, '>=', (string) $from->getTimestamp()))
                ->where(fn (Builder $samples) => $this->whereNumericString($samples, '<', (string) $to->addDay()->getTimestamp())))
            ->orderByDesc('project_date')
            ->pluck('id')
            ->map(fn (mixed $id): int => (int) $id)
            ->all();
    }

    /**
     * Compares the varchar sample_innoculated timestamp numerically (as legacy MySQL did) without casting,
     * so empty or non-numeric legacy values cannot break the query: longer digit strings are larger numbers.
     */
    private function whereNumericString(Builder $samples, string $operator, string $bound): void
    {
        $length = strlen($bound);
        $samples->whereRaw('LENGTH(sample_innoculated) '.($operator === '>=' ? '>' : '<').' ?', [$length])
            ->orWhere(fn (Builder $sameLength) => $sameLength
                ->whereRaw('LENGTH(sample_innoculated) = ?', [$length])
                ->where('sample_innoculated', $operator, $bound));
    }

    private function parseDate(string $date): ?CarbonImmutable
    {
        foreach (['!d-m-Y', '!Y-m-d'] as $format) {
            $parsed = DateTimeImmutable::createFromFormat($format, $date, new DateTimeZone('Europe/Amsterdam'));
            $errors = DateTimeImmutable::getLastErrors();

            if ($parsed !== false && ($errors === false || ($errors['warning_count'] === 0 && $errors['error_count'] === 0))) {
                return CarbonImmutable::instance($parsed);
            }
        }

        return null;
    }

    private function empty(string $status): array
    {
        return [
            'status' => $status,
            'projects' => [],
            'client' => null,
            'project_rows' => [],
            'sample_types' => [],
            'articles' => [],
        ];
    }

    /**
     * @return array{id: int, name: ?string, debit_number: ?string, categories: list<string>}
     */
    private function client(int $clientId): array
    {
        $client = Client::query()
            ->with(['categories' => fn ($categories) => $categories->orderBy('categories_clients.id')])
            ->find($clientId, ['id', 'name', 'debit_number']);

        return [
            'id' => $clientId,
            'name' => $client?->name,
            'debit_number' => $client?->debit_number,
            'categories' => $client?->categories->pluck('name')->all() ?? [],
        ];
    }

    /**
     * Port of legacy projectsController::createProjectTableLine.
     *
     * @param  Collection<int, Sample>  $samples
     * @param  array<int, true>  $automaticRodac
     */
    private function projectRow(Project $project, Collection $samples, array $automaticRodac): array
    {
        $fields = json_decode($project->custom_fields ?: '{}', true);
        $fields = is_array($fields) ? $fields : [];

        return [
            ...$project->only(['id', 'reference', 'revision', 'project_name']),
            ...$project->status()->toArray(),
            'billed' => $project->isBilled(),
            'samples_count' => $project->samples_count,
            'automatic_rodac' => $samples->filter(fn (Sample $sample): bool => isset($automaticRodac[$sample->id]))->count(),
            'has_rodac' => $samples->contains('sample_type', 'R'),
            'has_legionella' => $samples->contains('sample_type', 'L'),
            'sampling_date' => $fields['project_monster'] ?? null,
            'received_date' => $fields['project_ontvangst'] ?? null,
            'inoculation_date' => ProjectInoculation::fromValues($samples->pluck('sample_innoculated'))->date(),
            'url' => route('projects.search', $project),
        ];
    }

    /**
     * Port of legacy billingController::getAutomaticallyRodacSampleMap: normal samples whose analyses all
     * originate from the RODAC_ASSAYS_FOR_BILLING assays are billed as rodac.
     *
     * @param  Collection<int, Sample>  $samples
     * @return array<int, true>
     */
    private function automaticRodacSamples(Collection $samples): array
    {
        $rodacAssays = json_decode((string) $this->cvars->valueOrDefault('RODAC_ASSAYS_FOR_BILLING', '[]'), true);
        $rodacAssays = array_map('intval', is_array($rodacAssays) ? $rodacAssays : [$rodacAssays]);
        $normalSamples = $samples->where('sample_type', 'S')->pluck('id');

        if ($normalSamples->isEmpty()) {
            return [];
        }

        return SampleAnalysis::query()->whereIn('sample', $normalSamples)->get(['sample', 'original_assay_base'])
            ->groupBy('sample')
            ->filter(fn (Collection $analyses): bool => $analyses->every(
                fn (SampleAnalysis $analysis): bool => in_array((int) $analysis->original_assay_base, $rodacAssays, true),
            ))
            ->map(fn (): bool => true)
            ->all();
    }

    /**
     * Port of legacy billingController::getSampleTypes: per-type totals, the number of projects and distinct
     * receipt dates per type, and the Saturday (50%) and Sunday/holiday (100%) surcharges based on each sample's
     * inoculation date. Only "S" samples are split into normal/THT/automatic rodac; rodac samples always count as rodac.
     *
     * @param  Collection<int, Sample>  $samples
     * @param  array<int, true>  $automaticRodac
     * @param  Collection<int, Project>  $projects
     */
    private function sampleTypes(Collection $samples, array $automaticRodac, Collection $projects): array
    {
        $receiptDates = $projects->map(function (Project $project): ?string {
            $received = data_get(json_decode($project->custom_fields ?: '{}', true), 'project_ontvangst');

            return is_scalar($received) && ! in_array(trim((string) $received), ['', 'Onbekend'], true) ? trim((string) $received) : null;
        });
        $counts = collect(self::SAMPLE_TYPES)->map(fn (string $header, string $type): array => [
            'type' => $type, 'header' => $header, 'count' => 0, 'count50' => 0, 'count100' => 0, 'projects' => [], 'receipt_dates' => [],
        ])->all();

        foreach ($samples as $sample) {
            $type = match ($sample->sample_type) {
                'S' => isset($automaticRodac[$sample->id]) ? 'R' : (empty($sample->tht_code) ? 'S' : 'T'),
                'R', 'L' => $sample->sample_type,
                default => null,
            };

            if ($type === null) {
                continue;
            }

            $markup = $this->surchargeDay($sample->sample_innoculated);
            $counts[$type]['count']++;
            $counts[$type]['count50'] += $markup === 'saturday' ? 1 : 0;
            $counts[$type]['count100'] += in_array($markup, ['sunday', 'holiday'], true) ? 1 : 0;
            $counts[$type]['projects'][(int) $sample->project] = true;

            if (($received = $receiptDates[(int) $sample->project] ?? null) !== null) {
                $counts[$type]['receipt_dates'][$received] = true;
            }
        }

        return collect($counts)
            ->filter(fn (array $count): bool => $count['count'] > 0)
            ->map(fn (array $count): array => [
                ...collect($count)->except(['projects', 'receipt_dates'])->all(),
                'project_count' => count($count['projects']),
                'mengmonsters' => 'NA',
                'receipt_date_count' => count($count['receipt_dates']),
            ])
            ->values()
            ->all();
    }

    private function surchargeDay(mixed $inoculated): string
    {
        if (empty($inoculated) || ! is_numeric($inoculated)) {
            return 'normal';
        }

        return $this->calendar->getTypeOfSpecialDate(
            Carbon::createFromTimestamp((int) $inoculated, 'Europe/Amsterdam')->format('Y-m-d'),
        );
    }

    /**
     * Port of legacy billingController::getBilledArticles. Positives are counted over every analysis of
     * the assay (not only those with a requested confirmation), but are only reported when at least one
     * confirmation was requested and at least one result was detected.
     *
     * @param  list<int>  $projectIds
     */
    private function articles(array $projectIds): array
    {
        return SampleAnalysis::query()
            ->whereIn('project', $projectIds)
            ->whereHas('assayRecord')
            ->with('assayRecord:id,name,article_code,billable')
            ->get(['id', 'assay_base', 'conf_requested', 'storedResult'])
            ->groupBy('assay_base')
            ->map(function (Collection $analyses): array {
                $assay = $analyses->first()->assayRecord;
                $confRequested = $analyses->filter(fn (SampleAnalysis $analysis): bool => (int) $analysis->conf_requested === 1)->count();
                $positives = $confRequested > 0
                    ? $analyses->filter(fn (SampleAnalysis $analysis): bool => (bool) data_get($analysis->storedResult, 'hidden.detected'))->count()
                    : 0;

                return [
                    'assay_base' => (int) $assay->id,
                    'name' => $assay->name,
                    'billable' => (int) $assay->billable !== 0,
                    'article_code' => empty($assay->article_code) ? '-' : $assay->article_code,
                    'total' => $analyses->count(),
                    'conf_requested_count' => $confRequested,
                    'positives' => $positives > 0 ? $positives : null,
                ];
            })
            ->filter(fn (array $article): bool => $article['billable'])
            ->sort(fn (array $a, array $b): int => strcasecmp((string) $a['name'], (string) $b['name']) ?: $a['assay_base'] <=> $b['assay_base'])
            ->map(fn (array $article): array => collect($article)->except('billable')->all())
            ->values()
            ->all();
    }
}
