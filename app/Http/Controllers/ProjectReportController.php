<?php

namespace App\Http\Controllers;

use App\Actions\Reports\GenerateProjectReports;
use App\Actions\Reports\GetProjectReportOptions;
use App\Actions\Reports\GetReportingPreferences;
use App\Http\Requests\GenerateProjectReportRequest;
use App\Models\Export;
use App\Models\Project;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\HeaderUtils;
use UnexpectedValueException;

class ProjectReportController extends Controller
{
    public function index(Request $request, Project $project, GetProjectReportOptions $options): View
    {
        $data = $options->handle($project);

        return view('reports.index', [
            'project' => $project,
            'form' => [
                'action' => route('projects.reports.store', $project),
                'csrf' => csrf_token(),
                'project' => $project->only(['id', 'reference', 'project_name', 'auth_status', 'is_ready', 'special_type']),
                'projectUrl' => route('projects.search', ['project' => $project->id]),
                'clientName' => $project->getRelation('client')?->name,
                'clientNotes' => $project->getRelation('client')?->report_notes,
                'preferences' => $data['preferences'],
                'preferredTemplate' => $data['preferredTemplate'],
                'assayOrder' => $data['assayOrder'],
                'history' => $data['history'],
                'signatureAvailable' => $data['signatureAvailable'],
                'footersUrl' => $request->user()->can('settings.advanced') ? route('footers.index') : null,
                'canEditParameters' => $request->user()->can('reports.parameters.update'),
                'templatesUrl' => $request->user()->can('settings.advanced') ? route('docgen-templates.index') : null,
                'preferencesUrl' => route('projects.reports.preferences', $project),
                'portalPreferencesUrl' => $this->portalPreferencesUrl($project),
                'clientUrl' => $request->user()->can('clients.manage') ? route('clients.edit', $project->getRawOriginal('client')) : null,
                'templates' => array_map(fn (array $template): array => [
                    ...$template,
                    'associationsUrl' => $request->user()->can('settings.advanced')
                        ? route('docgen-associations.edit', ['docgenTemplate' => $template['id'], 'show_hidden' => 1]) : null,
                ], $data['templateAvailability']),
                'assays' => $data['assays']->map(fn ($assay): array => [
                    ...$assay->only(['id', 'name']),
                    'hidden' => (int) $assay->hide_report === 1,
                    'accredited' => strtoupper(trim(json_decode($assay->custom_fields ?: '{}', true, flags: JSON_THROW_ON_ERROR)['accred'] ?? '')) === 'Q',
                ])->all(),
                'samples' => $data['samples']->map(fn ($sample): array => [
                    ...$sample->only(['id', 'barcode', 'client_description', 'sample_note']),
                    'assayIds' => $sample->analyses->pluck('assay_base')->unique()->values()->all(),
                ])->all(),
                'old' => $request->session()->getOldInput(),
            ],
        ]);
    }

    public function preferences(Project $project, GetReportingPreferences $preferences): JsonResponse
    {
        return response()->json(['data' => $preferences->handle($project)])
            ->header('Cache-Control', 'private, no-store');
    }

    public function store(GenerateProjectReportRequest $request, Project $project, GenerateProjectReports $generate): Response|RedirectResponse
    {
        $result = $generate->handle($project, $request->validated(), (int) $request->user()->id);

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
