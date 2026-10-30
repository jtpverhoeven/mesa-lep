<?php

namespace App\Http\Controllers;

use App\Actions\Reports\GenerateProjectReports;
use App\Actions\Reports\GetProjectReportOptions;
use App\Http\Requests\GenerateProjectReportRequest;
use App\Models\DocgenTemplate;
use App\Models\Export;
use App\Models\Project;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\HeaderUtils;
use UnexpectedValueException;

class ProjectReportController extends Controller
{
    public function index(Request $request, Project $project, GetProjectReportOptions $options, bool $temporary = false): View
    {
        if (! $temporary && (int) $project->is_ready !== 1 && ! $request->boolean('continue')) {
            return view('reports.not-ready', ['project' => $project]);
        }

        $data = $options->handle($project, $temporary);

        return view('reports.index', [
            'project' => $project,
            'temporary' => $temporary,
            'form' => [
                'temporary' => $temporary,
                'action' => route($temporary ? 'projects.reports.temporary.store' : 'projects.reports.store', $project),
                'csrf' => csrf_token(),
                'project' => $project->only(['id', 'reference', 'project_name', 'auth_status', 'is_ready', 'special_type']),
                'projectUrl' => route('projects.search', ['project' => $project->id]),
                'clientName' => $project->getRelation('client')?->name,
                'clientNotes' => $project->getRelation('client')?->report_notes,
                'preferences' => $data['preferences'],
                'preferredTemplate' => $data['preferredTemplate'],
                'assayOrder' => $data['assayOrder'],
                'history' => $data['history'],
                'canEditParameters' => ! $temporary && $request->user()->can('reports.parameters.update'),
                'portalPreferencesUrl' => $this->portalPreferencesUrl($project),
                'clientUrl' => $request->user()->can('clients.manage') ? route('clients.edit', $project->getRawOriginal('client')) : null,
                'templates' => $data['templates']->map(fn (DocgenTemplate $template): array => [
                    ...$template->only(['id', 'name', 'folder']),
                    'language' => $template->language(),
                    'acceptsBulk' => $template->acceptsBulk(),
                ])->all(),
                'assays' => $data['assays']->map(fn ($assay): array => [
                    ...$assay->only(['id', 'name']),
                    'hidden' => (int) $assay->hide_report === 1,
                ])->all(),
                'samples' => $data['samples']->map(fn ($sample): array => [
                    ...$sample->only(['id', 'barcode', 'client_description', 'sample_note']),
                ])->all(),
                'old' => $request->session()->getOldInput(),
            ],
        ]);
    }

    public function overview(Request $request, Project $project): JsonResponse
    {
        $data = $request->validate(['page' => ['sometimes', 'integer', 'min:1']]);
        $disk = Storage::disk('local');
        $exports = Export::query()
            ->where('client', $project->getRawOriginal('client'))
            ->with('projectRecord:id,reference')
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->paginate(25, [
                'id', 'project', 'report_reference', 'true_reference', 'revision',
                'print_version', 'date', 'type', 'hash',
            ], 'page', (int) ($data['page'] ?? 1));

        return response()->json([
            'data' => $exports->getCollection()->map(function (Export $export) use ($disk): array {
                $reportProject = $export->getRelation('projectRecord');
                $hasPdf = $reportProject !== null
                    && preg_match('/\A[0-9a-f]{64}\z/', $export->hash) === 1
                    && $disk->exists($export->storagePath());

                return [
                    'id' => $export->id,
                    'reference' => $export->report_reference ?: $export->true_reference ?: $reportProject?->reference ?: '-',
                    'revision' => $export->revision,
                    'print_version' => $export->print_version,
                    'date' => is_numeric($export->date)
                        ? Carbon::createFromTimestamp((int) $export->date, 'Europe/Amsterdam')->format('d-m-Y')
                        : $export->date,
                    'type' => match ((int) $export->type) {
                        1 => 'Rapport',
                        2 => 'Voorlopig rapport',
                        3 => 'Rapport per monster',
                        default => 'Onbekend',
                    },
                    'url' => $hasPdf ? route('projects.reports.show', [$reportProject, $export]) : null,
                ];
            })->values(),
            'meta' => [
                'current_page' => $exports->currentPage(),
                'last_page' => $exports->lastPage(),
                'total' => $exports->total(),
            ],
        ])->header('Cache-Control', 'private, no-store');
    }

    public function store(GenerateProjectReportRequest $request, Project $project, GenerateProjectReports $generate, bool $temporary = false): Response|RedirectResponse
    {
        $result = $generate->handle($project, $request->validated(), (int) $request->user()->id, $temporary);

        if ($result['pdf'] !== null) {
            return response($result['pdf'], 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => HeaderUtils::makeDisposition('inline', $result['filename'], 'report.pdf'),
                'Cache-Control' => 'private, no-store',
                'X-Content-Type-Options' => 'nosniff',
            ]);
        }

        $exports = $result['exports'];

        return $exports->count() === 1
            ? to_route('projects.reports.show', [$project, $exports->first()])
            : to_route('projects.reports.batch', [$project, $exports->first()->transaction]);
    }

    public function show(Project $project, Export $export): BinaryFileResponse
    {
        $export->loadMissing(['clientRecord', 'projectRecord']);
        $disk = Storage::disk('local');
        abort_unless($disk->exists($export->storagePath()), 404, 'Het PDF-bestand ontbreekt. Controleer de import van de rapportopslag.');

        return response()->file($disk->path($export->storagePath()), [
            'Content-Type' => 'application/pdf', 'Cache-Control' => 'private, no-store', 'X-Content-Type-Options' => 'nosniff',
        ])->setContentDisposition('inline', $export->filename(), Str::ascii($export->filename()));
    }

    public function batch(Project $project, int $transaction): View
    {
        $exports = $project->exports()->where('transaction', $transaction)->with(['clientRecord', 'projectRecord'])->orderBy('id')->get();
        abort_if($exports->isEmpty(), 404);

        return view('reports.created', ['project' => $project, 'exports' => $exports]);
    }

    private function portalPreferencesUrl(Project $project): ?string
    {
        $pattern = config('services.glass.report_preferences_url');

        if (! is_string($pattern) || $pattern === '') {
            return null;
        }

        $url = strtr($pattern, ['{client}' => (string) $project->getRawOriginal('client'), '{project}' => (string) $project->portal_id]);

        if (! filter_var($url, FILTER_VALIDATE_URL) || ! in_array(parse_url($url, PHP_URL_SCHEME), ['http', 'https'], true)) {
            throw new UnexpectedValueException('The client portal reporting-preferences URL must use HTTP or HTTPS.');
        }

        return $url;
    }
}
