<?php

namespace App\Actions\Reports;

use App\Actions\Projects\DeauthorizeProject;
use App\Actions\Projects\ResolveProjectReference;
use App\ChangeTracking\ChangeTracker;
use App\Models\Export;
use App\Models\Project;
use App\Models\Sample;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Throwable;

class GenerateProjectReports
{
    public function __construct(
        private GetProjectReportOptions $reportOptions,
        private BuildProjectReport $build,
        private RenderReportPdf $render,
        private ChangeTracker $changes,
        private ResolveProjectReference $reference,
        private DeauthorizeProject $deauthorize,
    ) {}

    /**
     * A temporary report is a combined PDF of the whole project, with every parameter in its default order, in one
     * of the templates allowed for temporary reports. Issuing it withdraws the project's authorisation.
     *
     * @param  array<string, mixed>  $options
     * @return array{pdf: ?string, filename: string, exports: Collection<int, Export>}
     */
    public function handle(Project $project, array $options, int $userId, bool $temporary = false): array
    {
        $createdPaths = [];
        $deauthorized = false;

        try {
            $result = DB::transaction(function () use ($project, $options, $userId, $temporary, &$createdPaths, &$deauthorized): array {
                $analysisIds = $project->sampleAnalyses()->reorder('id')->lockForUpdate()->pluck('sampleanalysis.id')->all();
                $project = Project::query()->lockForUpdate()->findOrFail($project->id);

                if ($analysisIds !== $project->sampleAnalyses()->reorder('id')->pluck('sampleanalysis.id')->all()) {
                    throw ValidationException::withMessages(['report' => 'Het onderzoek in dit project is gewijzigd. Probeer opnieuw.']);
                }

                $project->load(['client', 'authorizedBy', 'samples.analyses.assayRecord']);
                if (blank($project->reference)) {
                    $project->reference = $this->reference->handle($project);
                }

                if (blank($project->reference)) {
                    throw ValidationException::withMessages(['report' => 'De projectreferentie ontbreekt. Controleer MESA_PROJECT_REFERENCE en de monsters van dit project.']);
                }
                $assayIds = $this->reportOptions->assays($project)->modelKeys();

                if (count($options['assay_order']) !== count($assayIds)
                    || array_diff($options['assay_order'], $assayIds) !== []
                    || array_diff($options['assay_ids'], $assayIds) !== []) {
                    throw ValidationException::withMessages(['report' => 'De analyses van dit project zijn gewijzigd. Laad de rapportinstellingen opnieuw.']);
                }

                if ($temporary) {
                    $options = [
                        ...$options,
                        'reportType' => 'projectReport',
                        'PDFtypeSelect' => 1,
                        'assay_ids' => $assayIds,
                        'assay_order' => $this->reportOptions->defaultAssayOrder($project),
                    ];
                }

                $template = $this->reportOptions->templates($project, $temporary)->firstWhere('folder', $options['templateSelect']);

                if ($template === null || ! $template->isSupported()) {
                    throw ValidationException::withMessages(['templateSelect' => 'Dit sjabloon is niet beschikbaar voor alle analyses van dit project, of de PDF-uitvoer is nog niet geport.']);
                }

                if ($template->language() !== ($options['languageSelect'] ?? $template->language())) {
                    throw ValidationException::withMessages(['languageSelect' => 'De taal en het rapportsjabloon komen niet overeen.']);
                }

                $samples = $project->samples->sortBy('id')->values();

                if ($options['reportType'] === 'projectSample') {
                    if (array_diff($options['exportSample'], $samples->modelKeys()) !== []) {
                        throw ValidationException::withMessages(['report' => 'De monsterselectie is gewijzigd. Laad de rapportinstellingen opnieuw.']);
                    }

                    $samples = $samples->whereIn('id', $options['exportSample'])->values();
                }

                $individual = (int) $options['PDFtypeSelect'] === 0;
                $preview = $options['reportExportType'] === 'preview';
                $groups = $individual
                    ? $samples->map(fn (Sample $sample): Collection => new Collection([$sample]))->all()
                    : [$samples];
                $reports = [];
                $exports = new Collection;
                $transaction = null;

                foreach ($groups as $group) {
                    $version = (int) $project->print_version + 1;
                    $report = $this->build->handle($project, $group, $options, $version);

                    if ($report === null) {
                        continue;
                    }

                    if ($preview) {
                        $reports[] = $report;

                        continue;
                    }

                    $pdf = $this->render->handle([$report], false, $temporary);
                    $export = $this->export($project, $group, $options['templateSelect'], $version, $individual, $temporary, $transaction);
                    $path = $export->storagePath();

                    if (Storage::disk('local')->exists($path)) {
                        throw ValidationException::withMessages(['report' => 'Deze rapportreferentie bestaat al. Bestaande PDF-bestanden worden niet overschreven; controleer de autorisatie en het printnummer.']);
                    }

                    $createdPaths[] = $path;

                    if (! Storage::disk('local')->put($path, $pdf)) {
                        throw new RuntimeException('The generated report could not be stored.');
                    }

                    $exports->push($export);
                    $transaction ??= $export->id;

                    if ((int) $project->auth_status === 1) {
                        $project->print_version = $version;
                        $project->save();
                    }
                }

                if ($preview) {
                    if ($reports === []) {
                        $this->noOutput();
                    }

                    return [
                        'pdf' => $this->render->handle($reports, true, $temporary),
                        'filename' => Export::safeFilename('MAZ-L '.$project->reference.'.'.$project->revision.'.'.((int) $project->print_version + 1)).'.pdf',
                        'exports' => $exports,
                    ];
                }

                if ($exports->isEmpty()) {
                    $this->noOutput();
                }

                if ($temporary) {
                    $project->save();
                    $deauthorized = $this->deauthorize->revoke(
                        $project, $userId, 'Voorlopig rapport', 'Voorlopig rapport uitgegeven, autorisatie automatisch ingetrokken',
                    )['deauthorized'];

                    return ['pdf' => null, 'filename' => '', 'exports' => $exports];
                }

                if ((int) $project->auth_status === 1) {
                    $project->update([
                        'rap_stat' => 1, 'rap_by' => $userId, 'rap_on' => (string) now()->timestamp,
                        'rap_rev' => $project->revision,
                    ]);
                }
                $project->save();

                $this->changes->changed(10, project: $project->id, event: 'Project uitgevoerd', userId: $userId);

                return ['pdf' => null, 'filename' => '', 'exports' => $exports];
            });
        } catch (Throwable $exception) {
            // Files are outside the database transaction; compensate only for files created by this attempt.
            foreach ($createdPaths as $path) {
                if (! Storage::disk('local')->delete($path)) {
                    Log::error('Could not remove a report after its database transaction failed.', ['path' => $path]);
                }
            }

            throw $exception;
        }

        if ($deauthorized) {
            $this->deauthorize->syncPortal($project->id, true);
        }

        return $result;
    }

    /** @param Collection<int, Sample> $samples */
    private function export(Project $project, Collection $samples, string $template, int $version, bool $individual, bool $temporary, ?int $transaction): Export
    {
        $sample = $samples->count() === 1 ? $samples->first() : null;
        $description = $sample?->client_description ?? '';
        $useDescription = $sample !== null && (in_array((int) $sample->source, [1, 3], true)
            || (trim($description) !== '' && mb_strtolower(trim($description)) !== 'geen omschrijving beschikbaar'));
        $export = $project->exports()->create([
            'transaction' => $transaction,
            'client' => $project->getRawOriginal('client'),
            'revision' => $project->revision,
            'print_version' => $version,
            'type' => $temporary ? 2 : ($individual ? 3 : 1),
            'naming_strategy' => $useDescription ? 'description' : 'reference',
            'naming_description' => $useDescription ? Str::substr(Export::safeFilename($description), 0, 64) : '',
            'template' => $template,
            'hash' => hash('sha256', $project->id.$project->revision.$version.$project->auth_status),
            'date' => (string) now()->timestamp,
            'report_reference' => $project->project_name,
            'true_reference' => $project->reference,
            'temporary' => (int) $temporary,
            'was_authorized' => $project->auth_status,
            'sample_id' => $sample?->id,
            'sample_id_scope' => $samples->modelKeys(),
        ]);

        if ($transaction === null) {
            $export->update(['transaction' => $export->id]);
        }

        return $export;
    }

    private function noOutput(): never
    {
        throw ValidationException::withMessages(['report' => 'Geen rapporteerbare resultaten gevonden voor de geselecteerde monsters en parameters.']);
    }
}
