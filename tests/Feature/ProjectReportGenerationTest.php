<?php

namespace Tests\Feature;

use App\Actions\Projects\AuthorizeProject;
use App\Actions\Reports\BuildProjectReport;
use App\Actions\Reports\GenerateProjectReports;
use App\Actions\Reports\GetProjectReportOptions;
use App\Actions\Reports\RenderReportPdf;
use App\Actions\Results\CalculateAnalysisResult;
use App\Models\Assay;
use App\Models\ChangeRevision;
use App\Models\Client;
use App\Models\Cvar;
use App\Models\DocgenTemplate;
use App\Models\Export;
use App\Models\Footer;
use App\Models\Project;
use App\Models\ReferenceSource;
use App\Models\ResearchProfile;
use App\Models\Sample;
use App\Models\SampleAnalysis;
use App\Models\SampleProcedure;
use App\Models\User;
use App\Support\AccountImages;
use App\Support\ReportHtml;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Mockery;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Process\Process;
use Tests\TestCase;

class ProjectReportGenerationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');

        foreach (glob(database_path('migrations/*.php')) as $path) {
            (require $path)->up();
        }
    }

    #[DataProvider('projectAuthorizationStates')]
    public function test_incomplete_projects_show_a_standalone_warning_without_loading_report_options_or_issuing(int $authorized): void
    {
        $fixture = $this->fixture();
        $fixture['project']->update(['is_ready' => 0, 'auth_status' => $authorized, 'portal_id' => 123]);
        Http::preventStrayRequests();

        $this->get(route('projects.reports.index', $fixture['project']))
            ->assertViewIs('reports.not-ready')
            ->assertSeeText('Project niet afgerond')
            ->assertSeeText('Weet u zeker dat u geen voorlopig rapport wilt aanmaken?')
            ->assertSee(route('projects.reports.temporary.index', $fixture['project']))
            ->assertSee(route('projects.reports.index', ['project' => $fixture['project'], 'continue' => 1]))
            ->assertSee(route('projects.search', ['project' => $fixture['project']->id]))
            ->assertSee(route('projects.overview.completed'))
            ->assertSee(route('projects.overview.authorized'))
            ->assertDontSee('<project-report-form', false);

        $this->assertNotIssued($fixture['project']);
        $this->assertDatabaseHas('projects', ['id' => $fixture['project']->id, 'is_ready' => 0, 'auth_status' => $authorized]);
        Http::assertNothingSent();
    }

    #[DataProvider('projectAuthorizationStates')]
    public function test_users_can_continue_from_the_warning_and_generate_an_incomplete_report(int $authorized): void
    {
        $fixture = $this->fixture();
        $fixture['project']->update(['is_ready' => 0, 'auth_status' => $authorized]);
        $continueUrl = route('projects.reports.index', ['project' => $fixture['project'], 'continue' => 1]);
        $this->get(route('projects.reports.index', $fixture['project']))
            ->assertViewIs('reports.not-ready')->assertSee($continueUrl);
        $this->get($continueUrl)
            ->assertViewIs('reports.index')
            ->assertViewHas('form', fn (array $form): bool => $form['temporary'] === false
                && $form['project']['is_ready'] === 0
                && $form['action'] === route('projects.reports.store', $fixture['project']));

        $response = $this->post(route('projects.reports.store', $fixture['project']), $this->reportOptions($fixture));

        $export = Export::query()->sole();
        $response->assertRedirect(route('projects.reports.show', [$fixture['project'], $export]))->assertSessionHasNoErrors();
        $this->assertDatabaseHas('exports', ['id' => $export->id, 'type' => 1, 'temporary' => 0, 'was_authorized' => $authorized]);
        $this->assertDatabaseHas('projects', ['id' => $fixture['project']->id, 'is_ready' => 0, 'auth_status' => $authorized]);
        $this->get($response->headers->get('Location'))->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $this->get(route('projects.reports.index', $fixture['project']))->assertViewIs('reports.not-ready');
    }

    #[DataProvider('projectAuthorizationStates')]
    public function test_ready_projects_open_report_settings_regardless_of_authorization(int $authorized): void
    {
        $fixture = $this->fixture();
        $fixture['project']->update(['auth_status' => $authorized]);

        $this->get(route('projects.reports.index', $fixture['project']))
            ->assertViewIs('reports.index')
            ->assertViewHas('temporary', false);
    }

    #[DataProvider('projectAuthorizationStates')]
    public function test_preliminary_report_settings_open_directly_for_incomplete_projects(int $authorized): void
    {
        $fixture = $this->fixture();
        $fixture['project']->update(['is_ready' => 0, 'auth_status' => $authorized]);
        $this->allowTemporaryTemplates();

        $this->get(route('projects.reports.temporary.index', $fixture['project']))
            ->assertViewIs('reports.index')
            ->assertViewHas('form', fn (array $form): bool => $form['temporary'] === true
                && $form['action'] === route('projects.reports.temporary.store', $fixture['project']));

        $this->assertNotIssued($fixture['project']);
        $this->assertDatabaseHas('projects', ['id' => $fixture['project']->id, 'auth_status' => $authorized]);
    }

    public static function projectAuthorizationStates(): array
    {
        return [
            'authorized' => [1],
            'unauthorized' => [0],
        ];
    }

    public function test_creation_issues_a_real_inline_pdf_and_preserves_legacy_export_state_without_sending(): void
    {
        $fixture = $this->fixture();
        Http::preventStrayRequests();
        Mail::fake();
        Queue::fake();

        $response = $this->post(route('projects.reports.store', $fixture['project']), $this->reportOptions($fixture));
        $response->assertRedirect()->assertSessionHasNoErrors();

        $export = Export::query()->firstOrFail();
        $response->assertRedirect(route('projects.reports.show', [$fixture['project'], $export]));
        $this->assertDatabaseHas('projects', ['id' => $fixture['project']->id, 'print_version' => 8, 'rap_stat' => 1, 'rap_by' => $fixture['user']->id, 'rap_rev' => 2, 'rap_on' => (string) now()->timestamp]);
        $this->assertDatabaseHas('exports', [
            'id' => $export->id, 'transaction' => $export->id, 'project' => $fixture['project']->id,
            'client' => $fixture['client']->id, 'revision' => 2, 'print_version' => 8,
            'type' => 1, 'temporary' => 0, 'was_authorized' => 1, 'template' => 'maz2020',
            'naming_strategy' => 'description', 'naming_description' => 'Monster 1',
            'sample_id' => $fixture['samples']->first()->id,
        ]);
        $this->assertSame($fixture['samples']->modelKeys(), $export->sample_id_scope);
        $this->assertSame(hash('sha256', $fixture['project']->id.'281'), $export->hash);
        $this->assertDatabaseHas('changetracker', ['type' => '10', 'event' => 'Project uitgevoerd', 'project' => $fixture['project']->id, 'user_id' => $fixture['user']->id]);
        Storage::disk('local')->assertExists($export->storagePath());
        $pdf = Storage::disk('local')->get($export->storagePath());
        $this->assertStringStartsWith('%PDF-', $pdf);
        $this->assertStringEndsWith('%%EOF', rtrim($pdf));
        $this->assertSame(2, preg_match_all('/\/Type\s*\/Page\b/', $pdf));
        $this->get($response->headers->get('Location'))->assertOk()->assertHeader('Content-Type', 'application/pdf');
        Mail::assertNothingSent();
        Queue::assertNothingPushed();
        Http::assertNothingSent();
    }

    public function test_individual_reports_share_a_transaction_and_each_receive_the_next_print_number(): void
    {
        $fixture = $this->fixture(2);

        $response = $this->post(route('projects.reports.store', $fixture['project']), $this->reportOptions($fixture, ['PDFtypeSelect' => 0]));

        $exports = Export::query()->orderBy('id')->get();
        $this->assertSame([8, 9], $exports->pluck('print_version')->all());
        $this->assertSame([3, 3], $exports->pluck('type')->all());
        $this->assertSame([$exports->first()->id, $exports->first()->id], $exports->pluck('transaction')->all());
        $this->assertSame([[1], [2]], $exports->pluck('sample_id_scope')->all());
        $this->assertSame(9, $fixture['project']->fresh()->print_version);
        $response->assertRedirect(route('projects.reports.batch', [$fixture['project'], $exports->first()->id]));
        $this->get($response->headers->get('Location'))->assertViewIs('reports.created')->assertSee('name="report-pdf"', false)->assertSee('Monster 1')->assertSee('Monster 2');

        foreach ($exports as $export) {
            Storage::disk('local')->assertExists($export->storagePath());
            $this->get(route('projects.reports.show', [$fixture['project'], $export]))->assertOk()->assertHeader('Content-Type', 'application/pdf');
        }
    }

    public function test_repeated_creation_retains_previous_pdfs_and_advances_only_the_print_version(): void
    {
        $fixture = $this->fixture();
        $url = route('projects.reports.store', $fixture['project']);
        $this->post($url, $this->reportOptions($fixture))->assertRedirect()->assertSessionHasNoErrors();
        $first = Export::query()->firstOrFail();
        $contents = Storage::disk('local')->get($first->storagePath());

        $this->post($url, $this->reportOptions($fixture))->assertRedirect()->assertSessionHasNoErrors();

        $this->assertSame([8, 9], Export::query()->orderBy('id')->pluck('print_version')->all());
        $this->assertSame(2, $fixture['project']->fresh()->revision);
        $this->assertSame($contents, Storage::disk('local')->get($first->storagePath()));
        $this->assertDatabaseCount('changetracker', 2);
    }

    public function test_preview_returns_a_pdf_without_export_records_files_or_issuance_state_changes(): void
    {
        $fixture = $this->fixture(2);
        $before = $fixture['project']->fresh()->only(['print_version', 'rap_stat', 'rap_on', 'rap_by', 'revision']);

        $response = $this->post(route('projects.reports.store', $fixture['project']), $this->reportOptions($fixture, ['reportExportType' => 'preview', 'PDFtypeSelect' => 0]));

        $response->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringStartsWith('%PDF-', $response->getContent());
        $this->assertSame(5, preg_match_all('/\/Type\s*\/Page\b/', $response->getContent()));
        $this->assertSame($before, $fixture['project']->fresh()->only(array_keys($before)));
        $this->assertDatabaseCount('exports', 0);
        $this->assertDatabaseCount('changetracker', 0);
        $this->assertSame([], Storage::disk('local')->files('reportStorage'));
    }

    public function test_selection_scopes_results_notes_and_legacy_sample_metadata(): void
    {
        $fixture = $this->fixture(2);
        $selected = $fixture['samples']->last();
        $fixture['project']->update(['project_notes' => json_encode([
            'project' => 'Algemene opmerking.',
            $fixture['samples']->first()->id => 'Niet opnemen.',
            $selected->id => 'Wel opnemen.',
        ])]);

        $report = app(BuildProjectReport::class)->handle(
            $fixture['project']->fresh(), new Collection([$selected]), $this->reportOptions($fixture), 8,
        );

        $this->assertSame([$selected->id], array_map(fn (array $row): int => $row['sample']->id, $report['samples']));
        $this->assertSame(['Algemeen', 'MAZ-L '.$selected->barcode], array_column($report['notes'], 'barcode'));
        $this->assertSame('Wel opnemen.', (string) $report['notes'][1]['text']);
        $this->assertStringContainsString('Omschrijving: Monster 2<br />Aanvullende informatie: Partij 2', (string) $report['samples'][0]['description']);
        $this->assertStringContainsString('Ruimte: Koelcel 1', (string) $report['samples'][0]['description']);
        $this->assertStringContainsString('Temperatuur: 4 C', (string) $report['samples'][0]['description']);

        $this->post(route('projects.reports.store', $fixture['project']), $this->reportOptions($fixture, ['reportType' => 'projectSample', 'exportSample' => [$selected->id]]))->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame([$selected->id], Export::query()->firstOrFail()->sample_id_scope);
    }

    public function test_template_eligibility_is_the_intersection_of_all_assays_including_hidden_ones(): void
    {
        $fixture = $this->fixture();
        $second = $this->assay(['name' => 'Verborgen analyse', 'hide_report' => 1]);
        $this->analysis($fixture['project'], $fixture['samples']->first(), $second, $fixture['source']);
        $other = DocgenTemplate::factory()->create(['folder' => 'legacyOverview']);
        $other->assays()->attach([$fixture['assay']->id => ['original_assay_id' => 1], $second->id => ['original_assay_id' => 2]]);
        $inactive = DocgenTemplate::factory()->create(['active' => false]);
        $inactive->assays()->attach([$fixture['assay']->id => ['original_assay_id' => 1], $second->id => ['original_assay_id' => 2]]);

        $templates = app(GetProjectReportOptions::class)->templates($fixture['project']->fresh());

        $this->assertSame([$other->id], $templates->modelKeys());
        $this->get(route('projects.reports.index', $fixture['project']))
            ->assertViewHas('form', fn (array $form): bool => $form['templates'] === []
                && $form['preferredTemplate'] === null);
        $this->postJson(route('projects.reports.store', $fixture['project']), $this->reportOptions($fixture, [
            'assay_ids' => [$fixture['assay']->id, $second->id], 'assay_order' => [$fixture['assay']->id, $second->id],
        ]))->assertUnprocessable()->assertJsonValidationErrors('templateSelect');
        $this->assertDatabaseCount('exports', 0);
    }

    #[DataProvider('preferenceResponses')]
    public function test_export_page_uses_portal_preferences_without_overriding_the_client_violation_setting(array $payload, int $status, bool $found, bool $warning): void
    {
        $fixture = $this->fixture();
        $fixture['project']->update(['portal_id' => 42]);
        config(['services.glass.base_url' => 'http://portal.test/api', 'services.glass.report_preferences_url' => 'https://portal.test/clients/{client}/preferences?project={project}']);
        Http::preventStrayRequests();
        Http::fake(['http://portal.test/api/report-preferences' => Http::response($payload, $status)]);

        $response = $this->get(route('projects.reports.index', $fixture['project']));

        $response->assertViewIs('reports.index')->assertViewHas('form', function (array $form) use ($fixture, $found, $warning): bool {
            return $form['preferences']['foundSettings'] === $found
                && ($form['preferences']['warning'] !== null) === $warning
                && $form['preferences']['showViolation'] === 1
                && $form['portalPreferencesUrl'] === 'https://portal.test/clients/'.$fixture['client']->id.'/preferences?project=42'
                && (! $found || ($form['preferences']['language'] === 'en' && $form['preferences']['noSamples'] === 5 && $form['preferences']['pdfTypeSelect'] === 1));
        });
        Http::assertSent(fn ($request): bool => $request->url() === 'http://portal.test/api/report-preferences' && $request->hasFile('id', 42));
        Http::assertSentCount(1);
    }

    public static function preferenceResponses(): array
    {
        return [
            'preferences found' => [['preferences' => ['foundSettings' => true, 'reportOutputType' => 'bulk', 'language' => 'en', 'numberOfSamples' => 0, 'highlightErrors' => false, 'selectedContactLists' => [4]]], 200, true, false],
            'no preferences' => [['preferences' => ['foundSettings' => false]], 200, false, false],
            'legacy application 404' => [['status' => '404'], 200, false, false],
            'HTTP 404' => [[], 404, false, false],
            'server failure is visible' => [[], 500, false, true],
            'malformed preferences are visible' => [['preferences' => ['foundSettings' => true]], 200, false, true],
            'portal application error is visible' => [['status' => '500', 'preferences' => ['foundSettings' => false]], 200, false, true],
        ];
    }

    public function test_report_page_hides_templates_with_missing_associations(): void
    {
        $fixture = $this->fixture();
        $fixture['template']->associations()->delete();

        $this->get(route('projects.reports.index', $fixture['project']))
            ->assertViewHas('form', fn (array $form): bool => $form['templates'] === []
                && $form['preferredTemplate'] === null);
    }

    public function test_report_page_offers_matching_styles_without_admin_configuration_diagnostics(): void
    {
        $fixture = $this->fixture();
        Footer::query()->delete();
        Storage::disk('local')->delete(AccountImages::signaturePath($fixture['user'], 'nl'));
        $unsupported = DocgenTemplate::factory()->create(['folder' => 'legacyOverview']);
        $unsupported->assays()->attach($fixture['assay']->id, ['original_assay_id' => $fixture['assay']->original_id]);
        $inactive = DocgenTemplate::factory()->create(['active' => false]);
        $inactive->assays()->attach($fixture['assay']->id, ['original_assay_id' => $fixture['assay']->original_id]);

        $this->get(route('projects.reports.index', $fixture['project']))
            ->assertViewHas('form', function (array $form) use ($fixture): bool {
                $this->assertSame([[
                    'id' => $fixture['template']->id,
                    'name' => 'MAZ 2020',
                    'folder' => 'maz2020',
                    'language' => 'nl',
                    'acceptsBulk' => 0,
                ]], $form['templates']);
                $this->assertArrayNotHasKey('signatureAvailable', $form);
                $this->assertArrayNotHasKey('templatesUrl', $form);
                $this->assertArrayNotHasKey('footersUrl', $form);

                return true;
            });
    }

    public function test_report_page_offers_no_styles_when_the_project_has_no_assays(): void
    {
        $fixture = $this->fixture();
        $fixture['project']->sampleAnalyses()->delete();

        $this->get(route('projects.reports.index', $fixture['project']))
            ->assertViewHas('form', fn (array $form): bool => $form['templates'] === []
                && $form['preferredTemplate'] === null);
    }

    public function test_earlier_reports_are_grouped_by_transaction_and_link_to_existing_pdfs_without_reissuing(): void
    {
        $fixture = $this->fixture();
        $first = Export::factory()->create(['project' => $fixture['project']->id, 'transaction' => 12, 'print_version' => 2, 'type' => 3]);
        $second = Export::factory()->create(['project' => $fixture['project']->id, 'transaction' => 12, 'print_version' => 3, 'type' => 3]);
        Export::factory()->create(['project' => $fixture['project']->id, 'transaction' => 20, 'type' => 2]);
        Export::factory()->create(['project' => 999, 'transaction' => 50]);

        $this->get(route('projects.reports.index', $fixture['project']))
            ->assertViewHas('form', function (array $form) use ($fixture, $first, $second): bool {
                return count($form['history']) === 2
                    && $form['history'][0]['transaction'] === 20
                    && $form['history'][0]['type'] === 'Voorlopig rapport'
                    && $form['history'][1]['type'] === 'Rapport per monster'
                    && array_column($form['history'][1]['content'], 'url') === [
                        route('projects.reports.show', [$fixture['project'], $first]),
                        route('projects.reports.show', [$fixture['project'], $second]),
                    ];
            });

        $this->assertSame(7, $fixture['project']->fresh()->print_version);
        $this->assertDatabaseCount('changetracker', 0);
    }

    public function test_client_report_overview_lists_the_clients_exports_with_metadata_and_available_pdf_links(): void
    {
        $fixture = $this->fixture();
        $otherProject = Project::query()->create([
            'client' => $fixture['client']->id,
            'subclient' => 0,
            'reference' => '261023',
            'project_name' => 'Andere klantreferentie',
        ]);
        $otherClientProject = Project::query()->create([
            'client' => $fixture['client']->id + 1,
            'subclient' => 0,
            'reference' => '999999',
        ]);
        $latest = Export::factory()->create([
            'client' => $fixture['client']->id,
            'project' => $fixture['project']->id,
            'report_reference' => 'Klantreferentie',
            'revision' => 4,
            'print_version' => 11,
            'date' => (string) Carbon::parse('2026-10-24 11:00:00', 'Europe/Amsterdam')->timestamp,
            'type' => 1,
        ]);
        $preliminary = Export::factory()->create([
            'client' => $fixture['client']->id,
            'project' => $fixture['project']->id,
            'report_reference' => 'Voorlopig rapport',
            'date' => (string) Carbon::parse('2026-10-23 11:00:00', 'Europe/Amsterdam')->timestamp,
            'type' => 2,
        ]);
        $perSample = Export::factory()->create([
            'client' => $fixture['client']->id,
            'project' => $otherProject->id,
            'report_reference' => 'Andere klantreferentie',
            'date' => (string) Carbon::parse('2026-10-22 11:00:00', 'Europe/Amsterdam')->timestamp,
            'type' => 3,
        ]);
        $unknown = Export::factory()->create([
            'client' => $fixture['client']->id,
            'project' => $fixture['project']->id,
            'date' => (string) Carbon::parse('2026-10-21 11:00:00', 'Europe/Amsterdam')->timestamp,
            'type' => 9,
        ]);
        $otherClientExport = Export::factory()->create([
            'client' => $fixture['client']->id + 1,
            'project' => $otherClientProject->id,
            'date' => (string) Carbon::parse('2026-10-25 11:00:00', 'Europe/Amsterdam')->timestamp,
        ]);
        Storage::disk('local')->put($latest->storagePath(), '%PDF-latest');
        Storage::disk('local')->put($perSample->storagePath(), '%PDF-other-project');

        $response = $this->getJson(route('projects.reports.overview', $fixture['project']));

        $response->assertJsonPaths([
            'data.0.id' => $latest->id,
            'data.0.reference' => 'Klantreferentie',
            'data.0.revision' => 4,
            'data.0.print_version' => 11,
            'data.0.date' => '24-10-2026',
            'data.0.type' => 'Rapport',
            'data.0.url' => route('projects.reports.show', [$fixture['project'], $latest]),
            'data.1.date' => '23-10-2026',
            'data.1.type' => 'Voorlopig rapport',
            'data.1.url' => null,
            'data.2.reference' => 'Andere klantreferentie',
            'data.2.type' => 'Rapport per monster',
            'data.2.url' => route('projects.reports.show', [$otherProject, $perSample]),
            'data.3.type' => 'Onbekend',
            'data.3.url' => null,
            'meta.current_page' => 1,
            'meta.last_page' => 1,
            'meta.total' => 4,
        ]);
        $this->assertSame(
            [$latest->id, $preliminary->id, $perSample->id, $unknown->id],
            array_column($response->json('data'), 'id'),
        );
        $this->assertNotContains($otherClientExport->id, array_column($response->json('data'), 'id'));

        $this->getJson(route('projects.search.show', $fixture['project']))
            ->assertJsonPath('data.report_overview_url', route('projects.reports.overview', $fixture['project']));
    }

    public function test_client_report_overview_paginates_exports_and_rejects_invalid_pages(): void
    {
        $fixture = $this->fixture();
        $exports = Export::factory()->count(26)->create([
            'client' => $fixture['client']->id,
            'project' => $fixture['project']->id,
            'date' => (string) now()->timestamp,
        ]);

        $response = $this->getJson(route('projects.reports.overview', ['project' => $fixture['project'], 'page' => 2]));

        $response->assertJsonPaths([
            'data.0.id' => $exports->first()->id,
            'meta.current_page' => 2,
            'meta.last_page' => 2,
            'meta.total' => 26,
        ]);
        $this->getJson(route('projects.reports.overview', ['project' => $fixture['project'], 'page' => 0]))
            ->assertUnprocessable();
    }

    public function test_default_parameter_order_interleaves_sample_positions_like_the_legacy_sorter(): void
    {
        $fixture = $this->fixture(2);
        $second = $this->assay(['name' => 'B']);
        $third = $this->assay(['name' => 'C']);
        $fourth = $this->assay(['name' => 'D']);
        $this->analysis($fixture['project'], $fixture['samples'][0], $second, $fixture['source'])->update(['project_order' => 2]);
        $fixture['analyses'][1]->update(['assay_base' => $third->id, 'project_order' => 1]);
        $this->analysis($fixture['project'], $fixture['samples'][1], $fourth, $fixture['source'])->update(['project_order' => 2]);

        $options = app(GetProjectReportOptions::class)->handle($fixture['project']->fresh());

        $this->assertSame([$fixture['assay']->id, $third->id, $second->id, $fourth->id], $options['assayOrder']);
    }

    public function test_a_dutch_certificate_cannot_be_issued_with_an_english_language_selection(): void
    {
        $fixture = $this->fixture();

        $this->postJson(route('projects.reports.store', $fixture['project']), $this->reportOptions($fixture, ['languageSelect' => 'en']))
            ->assertUnprocessable()->assertJsonValidationErrors('languageSelect');

        $this->assertNotIssued($fixture['project']);
    }

    #[DataProvider('legacyReferenceModes')]
    public function test_missing_project_references_use_the_existing_legacy_mode_without_changing_preview_state(string $mode, string $expected): void
    {
        $fixture = $this->fixture();
        $fixture['project']->update(['reference' => null]);
        Cvar::query()->create(['cvar' => 'MESA_PROJECT_REFERENCE', 'value' => $mode, 'default' => '3', 'description' => 'Project reference mode']);

        $this->get(route('projects.reports.index', $fixture['project']))
            ->assertViewHas('form', fn (array $form): bool => $form['project']['reference'] === $expected);
        $this->post(route('projects.reports.store', $fixture['project']), $this->reportOptions($fixture, ['reportExportType' => 'preview']))
            ->assertOk()->assertHeader('Content-Type', 'application/pdf');

        $this->assertNull($fixture['project']->fresh()->reference);
        $this->assertNotIssued($fixture['project']);

        $this->post(route('projects.reports.store', $fixture['project']), $this->reportOptions($fixture))->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame($expected, $fixture['project']->fresh()->reference);
        $this->assertSame($expected, Export::query()->firstOrFail()->true_reference);
    }

    public static function legacyReferenceModes(): array
    {
        return [
            'project ID' => ['1', '1'],
            'first sample ID' => ['2', '1'],
            'first sample barcode' => ['3', '261024001'],
            'empty override uses configured default' => ['', '261024001'],
        ];
    }

    public function test_existing_project_references_are_not_replaced_by_the_current_reference_mode(): void
    {
        $fixture = $this->fixture();
        Cvar::query()->create(['cvar' => 'MESA_PROJECT_REFERENCE', 'value' => '1', 'default' => '3', 'description' => 'Project reference mode']);

        $this->post(route('projects.reports.store', $fixture['project']), $this->reportOptions($fixture))->assertRedirect()->assertSessionHasNoErrors();

        $this->assertSame('261024', $fixture['project']->fresh()->reference);
        $this->assertSame('261024', Export::query()->firstOrFail()->true_reference);
    }

    public function test_a_missing_reference_and_unconfigured_reference_mode_do_not_produce_a_blank_certificate_number(): void
    {
        $fixture = $this->fixture();
        $fixture['project']->update(['reference' => null]);

        $this->postJson(route('projects.reports.store', $fixture['project']), $this->reportOptions($fixture))->assertUnprocessable()->assertJsonValidationErrors('report');

        $this->assertNotIssued($fixture['project']);
    }

    public function test_missing_footer_rolls_back_issuance_instead_of_substituting_unapproved_wording(): void
    {
        $fixture = $this->fixture();
        Footer::query()->delete();

        $this->postJson(route('projects.reports.store', $fixture['project']), $this->reportOptions($fixture))
            ->assertUnprocessable()->assertJsonValidationErrors('report');

        $this->assertNotIssued($fixture['project']);
    }

    public function test_a_missing_authorizer_signature_is_reported_without_issuing_an_unsigned_certificate(): void
    {
        $fixture = $this->fixture();
        Storage::disk('local')->delete(AccountImages::signaturePath($fixture['user'], 'nl'));

        $this->postJson(route('projects.reports.store', $fixture['project']), $this->reportOptions($fixture))
            ->assertUnprocessable()->assertJsonValidationErrors('report');

        $this->assertNotIssued($fixture['project']);
    }

    public function test_empty_reportable_output_does_not_mark_the_project_as_reported(): void
    {
        $fixture = $this->fixture();
        $fixture['assay']->update(['hide_report' => 1]);

        $this->postJson(route('projects.reports.store', $fixture['project']), $this->reportOptions($fixture))
            ->assertUnprocessable()->assertJsonValidationErrors('report');

        $this->assertNotIssued($fixture['project']);
    }

    public function test_failure_in_a_later_individual_report_removes_earlier_files_and_rolls_back_all_versions(): void
    {
        $fixture = $this->fixture(2);
        $secondAssay = $this->assay(['custom_fields' => json_encode(['raportnaam' => 'Andere analyse', 'accred' => '', 'resultin' => 'kve/g'])]);
        $second = $fixture['analyses'][1];
        $second->update(['assay' => $secondAssay->id, 'assay_base' => $secondAssay->id]);
        $second->roamingAnalysis->update(['assay' => $secondAssay->id]);
        $fixture['template']->assays()->attach($secondAssay->id, ['original_assay_id' => $secondAssay->original_id]);
        Footer::query()->where('q', 0)->delete();

        $this->postJson(route('projects.reports.store', $fixture['project']), $this->reportOptions($fixture, [
            'PDFtypeSelect' => 0, 'assay_ids' => [$fixture['assay']->id, $secondAssay->id], 'assay_order' => [$fixture['assay']->id, $secondAssay->id],
        ]))->assertUnprocessable()->assertJsonValidationErrors('report');

        $this->assertNotIssued($fixture['project']);
    }

    public function test_existing_report_files_are_never_overwritten_when_a_reference_collides(): void
    {
        $fixture = $this->fixture();
        $path = 'reportStorage/'.hash('sha256', $fixture['project']->id.'281').'.pdf';
        Storage::disk('local')->put($path, 'Existing imported report');

        $this->postJson(route('projects.reports.store', $fixture['project']), $this->reportOptions($fixture))
            ->assertUnprocessable()->assertJsonValidationErrors('report');

        $this->assertSame('Existing imported report', Storage::disk('local')->get($path));
        $this->assertDatabaseCount('exports', 0);
        $this->assertSame(7, $fixture['project']->fresh()->print_version);
    }

    public function test_foreign_samples_invalid_order_and_unauthorised_parameter_changes_are_rejected(): void
    {
        $fixture = $this->fixture();
        $other = Project::query()->create(['client' => $fixture['client']->id, 'subclient' => 0]);
        $foreign = $this->sample($other, 99);
        $url = route('projects.reports.store', $fixture['project']);

        $this->postJson($url, $this->reportOptions($fixture, ['reportType' => 'projectSample', 'exportSample' => [$foreign->id]]))->assertUnprocessable()->assertJsonValidationErrors('exportSample.0');
        $this->postJson($url, $this->reportOptions($fixture, ['assay_order' => [99999]]))->assertUnprocessable()->assertJsonValidationErrors('assay_order');
        Gate::define('reports.parameters.update', fn (): bool => false);
        $this->postJson($url, $this->reportOptions($fixture, ['assay_ids' => []]))->assertUnprocessable()->assertJsonValidationErrors('assay_ids');
        $this->assertNotIssued($fixture['project']);
    }

    public function test_downloads_of_imported_reports_are_project_scoped_and_preserve_the_stored_bytes(): void
    {
        $fixture = $this->fixture();
        $export = Export::factory()->create(['project' => $fixture['project']->id, 'client' => $fixture['client']->id]);
        $bytes = "%PDF-1.4\nImported legacy report bytes";
        Storage::disk('local')->put($export->storagePath(), $bytes);
        $other = Project::query()->create(['client' => $fixture['client']->id, 'subclient' => 0]);

        $response = $this->get(route('projects.reports.show', [$fixture['project'], $export]));

        $response->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $this->assertSame($bytes, file_get_contents($response->baseResponse->getFile()->getPathname()));
        $this->get(route('projects.reports.show', [$other, $export]))->assertNotFound();
        $this->assertSame(7, $fixture['project']->fresh()->print_version);
        $this->assertDatabaseCount('changetracker', 0);
    }

    public function test_reporting_endpoints_require_authentication_and_generation_permission(): void
    {
        $project = Project::query()->create(['client' => 1, 'subclient' => 0]);
        $this->get(route('projects.reports.index', $project))->assertRedirect(route('login'));
        $this->get(route('projects.reports.index', ['project' => $project, 'continue' => 1]))->assertRedirect(route('login'));
        $this->getJson(route('projects.reports.overview', $project))->assertUnauthorized();
        $this->postJson(route('projects.reports.store', $project), [])->assertUnauthorized();
        $this->get(route('projects.reports.temporary.index', $project))->assertRedirect(route('login'));
        $this->postJson(route('projects.reports.temporary.store', $project), [])->assertUnauthorized();
        $this->actingAs(User::factory()->create(['enabled' => 1]));
        Gate::define('projects.view', fn (): bool => true);
        Gate::define('reports.generate', fn (): bool => false);
        $this->get(route('projects.reports.index', $project))->assertForbidden();
        $this->get(route('projects.reports.index', ['project' => $project, 'continue' => 1]))->assertForbidden();
        $this->getJson(route('projects.reports.overview', $project))->assertForbidden();
        $this->postJson(route('projects.reports.store', $project), [])->assertForbidden();
        $this->get(route('projects.reports.temporary.index', $project))->assertForbidden();
        $this->postJson(route('projects.reports.temporary.store', $project), [])->assertForbidden();
        $this->getJson(route('projects.search.show', $project))->assertJsonPaths([
            'data.report_url' => null,
            'data.temporary_report_url' => null,
            'data.report_overview_url' => null,
        ]);
    }

    #[DataProvider('legacyIndicators')]
    public function test_result_indicators_preserve_legacy_threshold_and_disposition_rules(string $output, string $reference, ?string $disposition, bool $highlight, bool $trip, bool $indicator, string $expected): void
    {
        $fixture = $this->fixture();
        $analysis = $fixture['analyses'][0];
        $snapshot = $analysis->storedResult;
        $snapshot['output']['kve'] = $output;
        $snapshot['disposition']['kve'] = $disposition;
        $analysis->update(['storedResult' => $snapshot]);
        $analysis->roamingAnalysis->update(['reference' => ['ref_kve' => $reference]]);
        $fixture['assay']->update(['uses_indicator' => $indicator, 'uses_trip_indicator' => $trip]);

        $report = app(BuildProjectReport::class)->handle($fixture['project']->fresh(), $fixture['samples'], $this->reportOptions($fixture, ['showViolation' => (int) $highlight]), 8);

        $this->assertSame($expected, $report['samples'][0]['results'][$fixture['assay']->id]['indicator']);
    }

    public static function legacyIndicators(): array
    {
        return [
            'above reference' => ['1200', '1000', '+', true, true, true, '#ff0000'],
            'equal reference' => ['1000', '1000', '-', true, true, true, '#7bc144'],
            'greater-than equality retains legacy green' => ['>1000', '1000', '+', true, true, true, '#7bc144'],
            'less-than result above reference is indeterminate' => ['<2000', '1000', null, true, true, true, 'white'],
            'greater-than result below reference is indeterminate' => ['>500', '1000', null, true, true, true, 'white'],
            'below one is treated as zero' => ['<1', '1000', '-', true, true, true, '#7bc144'],
            'positive text' => ['Aangetoond', '1000', '+', true, true, true, '#ff0000'],
            'negative text' => ['Niet aangetoond', '1000', '-', true, true, true, '#7bc144'],
            'indeterminate text' => ['Onbekend', '1000', '.', true, true, true, 'white'],
            'positive text without reference' => ['Aangetoond', '', '+', true, true, true, '#ff0000'],
            'numeric result without reference' => ['1200', '', null, true, true, true, 'white'],
            'trip disabled' => ['1200', '1000', '+', true, false, true, 'white'],
            'highlight disabled' => ['1200', '1000', '+', false, true, true, 'white'],
            'indicator disabled' => ['1200', '1000', '+', true, true, false, 'white'],
            'decimal reference keeps legacy digit comparison' => ['2', '1.5', null, true, true, true, '#7bc144'],
        ];
    }

    public function test_authorisation_followed_by_generation_uses_the_existing_frozen_result_pipeline(): void
    {
        $fixture = $this->fixture();
        $fixture['project']->update(['auth_status' => 0, 'auth_by' => null]);
        $analysis = $fixture['analyses'][0];
        $analysis->update(['storedResult' => null]);
        $analysis->vetoResults()->create(['parameter' => 'kve', 'result' => '0', 'reason' => 'Controle', 'disposition' => '-']);
        app(CalculateAnalysisResult::class)->handle($analysis);
        Gate::define('projects.authorisation.manage', fn (): bool => true);
        config(['services.glass.base_url' => 'http://portal.test/api']);
        $url = 'http://portal.test/api/projectAuthorized/'.$fixture['project']->id;
        Http::preventStrayRequests();
        Http::fake([$url => Http::response(['ok' => true])]);

        $this->postJson(route('projects.authorization.store', $fixture['project']), ['quiet' => true])
            ->assertOk()->assertJsonPath('data.authorized', true);
        $this->post(route('projects.reports.store', $fixture['project']), $this->reportOptions($fixture))->assertRedirect()->assertSessionHasNoErrors();

        $this->assertSame('0', $analysis->fresh()->storedResult['output']['kve']);
        $this->assertDatabaseHas('exports', ['project' => $fixture['project']->id, 'was_authorized' => 1]);
        Http::assertSentCount(1);
    }

    public function test_certificate_html_retains_safe_superscripts_and_drops_executable_or_external_content(): void
    {
        $html = (string) ReportHtml::richText('<p onclick="alert(1)">Goedgekeurde tekst 10<sup>3</sup><br>regel 2</p><img src="file:///etc/passwd"><script>unsafe()</script><!--mpdf <img src="https://external.test/image"> mpdf-->');

        $this->assertStringContainsString('Goedgekeurde tekst 10<sup class="sup">3</sup>', $html);
        $this->assertStringContainsString('regel 2', $html);
        $this->assertStringNotContainsString('onclick', $html);
        $this->assertStringNotContainsString('file://', $html);
        $this->assertStringNotContainsString('unsafe()', $html);
        $this->assertStringNotContainsString('external.test', $html);
    }

    public function test_actual_certificate_pdf_preserves_fixed_wording_pagination_and_authorised_results(): void
    {
        if ((new ExecutableFinder)->find('pdftotext') === null) {
            $this->markTestSkipped('Install poppler-utils in Sail to verify the actual certificate PDF text.');
        }

        $fixture = $this->fixture(2);
        $this->post(route('projects.reports.store', $fixture['project']), $this->reportOptions($fixture))->assertRedirect()->assertSessionHasNoErrors();
        $export = Export::query()->firstOrFail();
        $text = (new Process(['pdftotext', '-layout', '-enc', 'UTF-8', Storage::disk('local')->path($export->storagePath()), '-']))->mustRun()->getOutput();
        $pages = array_values(array_filter(explode("\f", $text), fn (string $page): bool => trim($page) !== ''));
        $normalized = preg_replace('/\s+/u', ' ', str_replace(
            ["\u{FB00}", "\u{FB01}", "\u{FB02}", "\u{FB03}", "\u{FB04}"],
            ['ff', 'fi', 'fl', 'ffi', 'ffl'],
            $text,
        ));

        $this->assertCount(3, $pages);
        foreach ([
            'Micro-Analyse Zeeland B.V.', 'Cloosterstraat 81', '4587 CB Kloosterzande', 'KvK nr: 60584904',
            'Tel: +31 (0)114 68 3093', 'BTW: NL853971985B01', 'www.microanalyse.nl', 'IBAN: NL60INGB0674323734',
            'info@microanalyse.nl', 'BIC: INGBNL2A', 'Analysecertificaat', 'Datum bemonstering',
            'Datum ontvangst', 'Datum aanvang analyse(s)', 'Referentie Micro-Analyse Zeeland', 'Referentie Opdrachtgever',
            'Opdrachtgever', 'Monsteromschrijving', 'MAZ monsternr.', 'Monsterneming', 'Eenheid',
            'Resultaat', 'Referentiewaarde', 'Bron', 'Methodeverwijzingen van uitgevoerde verrichtingen',
            'Techniek', 'Conformiteit', 'Referentiemethode', 'Opmerkingen, opinies en interpretaties:',
            'Aanvullende informatie:', 'Goedgekeurde RvA certificaattekst.', 'Totaal kiemgetal', 'Interne norm',
            'MAZ-L 261024.2.8', 'Pagina 1 van 3', 'Pagina 2 van 3', 'Pagina 3 van 3',
        ] as $wording) {
            $this->assertStringContainsString($wording, $normalized);
        }
        $this->assertStringContainsString('MAZ-L261024001', $pages[0]);
        $this->assertStringNotContainsString('MAZ-L261024002', $pages[0]);
        $this->assertStringContainsString('MAZ-L261024002', $pages[1]);
        $this->assertStringContainsString('1.000', $pages[0]);
        $this->assertStringContainsString('1200', $pages[0]);
        $this->assertStringNotContainsString('VOORBEELD', $text);
        $this->assertStringNotContainsString('NIET GEAUTHORISEERD', $text);
        $this->assertSame(hash_file('sha256', base_path('legacy/app/docgen/pdf/maz2020/style.css')), hash_file('sha256', resource_path('views/reports/pdf/maz2020/style.css')));
    }

    public function test_individual_preview_does_not_carry_the_rva_logo_into_a_non_accredited_report(): void
    {
        if ((new ExecutableFinder)->find('pdfimages') === null) {
            $this->markTestSkipped('Install poppler-utils in Sail to inspect the PDF accreditation images.');
        }

        $fixture = $this->fixture(2);
        $secondAssay = $this->assay(['custom_fields' => '{"raportnaam":"Niet geaccrediteerd","accred":""}']);
        $fixture['analyses'][1]->update(['assay_base' => $secondAssay->id]);
        $fixture['template']->assays()->attach($secondAssay->id, ['original_assay_id' => $secondAssay->original_id]);

        $response = $this->post(route('projects.reports.store', $fixture['project']), $this->reportOptions($fixture, [
            'reportExportType' => 'preview', 'PDFtypeSelect' => 0,
            'assay_ids' => [$fixture['assay']->id, $secondAssay->id], 'assay_order' => [$fixture['assay']->id, $secondAssay->id],
        ]))->assertOk()->assertHeader('Content-Type', 'application/pdf');

        Storage::disk('local')->put('preview-qc.pdf', $response->getContent());
        try {
            $images = (new Process(['pdfimages', '-list', Storage::disk('local')->path('preview-qc.pdf')]))->mustRun()->getOutput();
        } finally {
            Storage::disk('local')->delete('preview-qc.pdf');
        }
        [$width, $height] = getimagesize(resource_path('views/reports/pdf/maz2020/assets/L420-color-nl.jpg'));
        preg_match_all('/^\s*(\d+)\s+\d+\s+image\s+'.$width.'\s+'.$height.'\s/m', $images, $matches);

        $this->assertContains('1', $matches[1]);
        $this->assertContains('2', $matches[1]);
        $this->assertNotContains('4', $matches[1]);
        $this->assertNotContains('5', $matches[1]);
        $this->assertNotIssued($fixture['project']);
    }

    public function test_profile_bound_analyses_use_the_profile_reference_instead_of_roaming_settings(): void
    {
        $fixture = $this->fixture();
        $profile = ResearchProfile::query()->create([
            'original_id' => 1, 'name' => 'Onderzoeksprofiel', 'global' => 0, 'client' => $fixture['client']->id,
        ]);
        $settings = $profile->assayProfiles()->create([
            'assay' => $fixture['assay']->id, 'dillutions' => [1], 'reference' => ['ref_kve' => '2500'],
            'reference_source' => $fixture['source']->id,
        ]);
        $fixture['analyses'][0]->update(['profile' => $profile->id, 'assay' => $settings->id, 'roaming_id' => null]);

        $report = app(BuildProjectReport::class)->handle($fixture['project']->fresh(), $fixture['samples'], $this->reportOptions($fixture), 8);

        $this->assertSame('2.500', $report['samples'][0]['results'][$fixture['assay']->id]['reference']);
        $this->assertSame('Interne norm', $report['samples'][0]['results'][$fixture['assay']->id]['source']);
    }

    public function test_selected_reports_keep_the_earliest_project_inoculation_date_and_the_issued_reference(): void
    {
        $fixture = $this->fixture(2);
        $fixture['samples']->first()->update(['sample_innoculated' => (string) Carbon::parse('2026-10-22 10:00:00', 'Europe/Amsterdam')->timestamp]);
        $fixture['project']->update(['project_extra' => '{"project_reference":"Must not replace the issued reference"}']);

        $report = app(BuildProjectReport::class)->handle($fixture['project']->fresh(), new Collection([$fixture['samples']->last()]), $this->reportOptions($fixture), 8);

        $this->assertSame('22-10-2026', $report['info']['project_inzet']);
        $this->assertSame('261024.2.8', $report['info']['project_reference']);
    }

    #[DataProvider('samplingVisibility')]
    public function test_parameter_order_and_method_numbers_account_for_hidden_sampling_procedures(bool $hidden, array $numbers): void
    {
        $fixture = $this->fixture();
        $fixture['procedure']->update(['hide' => (int) $hidden]);
        $second = $this->assay(['name' => 'Tweede analyse']);
        $this->analysis($fixture['project'], $fixture['samples']->first(), $second, $fixture['source']);

        $report = app(BuildProjectReport::class)->handle($fixture['project']->fresh(), $fixture['samples'], $this->reportOptions($fixture, [
            'assay_ids' => [$fixture['assay']->id, $second->id], 'assay_order' => [$second->id, $fixture['assay']->id],
        ]), 8);

        $this->assertSame([$second->id, $fixture['assay']->id], array_keys($report['methods']));
        $this->assertSame($numbers, array_column($report['methods'], 'number'));
        $this->assertSame($hidden ? null : 1, $report['sampling'][$fixture['procedure']->id]['number']);
    }

    public static function samplingVisibility(): array
    {
        return ['visible' => [false, [2, 3]], 'hidden' => [true, [1, 2]]];
    }

    public function test_sampling_accreditation_alone_does_not_select_an_accredited_assay_footer(): void
    {
        $fixture = $this->fixture();
        $fixture['assay']->update(['custom_fields' => '{"raportnaam":"Analyse","accred":"","resultin":"kve/g"}']);
        $fixture['procedure']->update(['fields' => '{"door":"Opdrachtgever","accred":"Q"}']);

        $report = app(BuildProjectReport::class)->handle($fixture['project']->fresh(), $fixture['samples'], $this->reportOptions($fixture), 8);

        $this->assertFalse($report['accredited']);
        $this->assertSame('Goedgekeurde algemene certificaattekst.', (string) $report['footer']);
    }

    public function test_unauthorised_generation_preserves_legacy_auditing_without_advancing_project_issuance_flags(): void
    {
        $fixture = $this->fixture();
        $fixture['project']->update(['auth_status' => 0, 'auth_by' => null]);

        $this->post(route('projects.reports.store', $fixture['project']), $this->reportOptions($fixture))->assertRedirect()->assertSessionHasNoErrors();

        $this->assertDatabaseHas('exports', ['project' => $fixture['project']->id, 'was_authorized' => 0, 'print_version' => 8]);
        $this->assertDatabaseHas('projects', ['id' => $fixture['project']->id, 'print_version' => 7, 'rap_stat' => 0]);
        $this->assertDatabaseHas('changetracker', ['project' => $fixture['project']->id, 'type' => '10', 'event' => 'Project uitgevoerd']);
    }

    public function test_analysis_changes_after_form_validation_are_rejected_inside_the_issuance_transaction(): void
    {
        $fixture = $this->fixture();
        $options = $this->reportOptions($fixture);
        $second = $this->assay();
        $this->analysis($fixture['project'], $fixture['samples']->first(), $second, $fixture['source']);
        $fixture['template']->assays()->attach($second->id, ['original_assay_id' => $second->original_id]);

        try {
            app(GenerateProjectReports::class)->handle($fixture['project'], $options, $fixture['user']->id);
            $this->fail('A stale analysis selection must not issue a partial certificate.');
        } catch (ValidationException $exception) {
            $this->assertSame(['De analyses van dit project zijn gewijzigd. Laad de rapportinstellingen opnieuw.'], $exception->errors()['report']);
        }

        $this->assertNotIssued($fixture['project']);
    }

    public function test_sample_changes_after_form_validation_do_not_silently_shorten_the_requested_scope(): void
    {
        $fixture = $this->fixture(2);
        $selected = $fixture['samples']->last();
        $options = $this->reportOptions($fixture, ['reportType' => 'projectSample', 'exportSample' => [$selected->id]]);
        $other = Project::query()->create(['client' => $fixture['client']->id, 'subclient' => 0]);
        $selected->update(['project' => $other->id]);

        try {
            app(GenerateProjectReports::class)->handle($fixture['project'], $options, $fixture['user']->id);
            $this->fail('A moved sample must not silently disappear from the issued scope.');
        } catch (ValidationException $exception) {
            $this->assertSame(['De monsterselectie is gewijzigd. Laad de rapportinstellingen opnieuw.'], $exception->errors()['report']);
        }

        $this->assertNotIssued($fixture['project']);
    }

    public function test_temporary_report_page_only_offers_templates_allowed_for_temporary_reports_and_locks_the_parameters(): void
    {
        $fixture = $this->fixture();
        $other = DocgenTemplate::factory()->create(['name' => 'Overzicht', 'folder' => 'legacyOverview', 'view_order' => 2]);
        $other->assays()->attach($fixture['assay']->id, ['original_assay_id' => $fixture['assay']->original_id]);
        $this->allowTemporaryTemplates('["maz2020"]');

        $this->get(route('projects.reports.index', $fixture['project']))->assertOk()
            ->assertViewHas('form', fn (array $form): bool => $form['temporary'] === false
                && $form['action'] === route('projects.reports.store', $fixture['project'])
                && $form['canEditParameters'] === true
                && array_column($form['templates'], 'folder') === ['maz2020']);
        $this->get(route('projects.reports.temporary.index', $fixture['project']))->assertOk()
            ->assertViewIs('reports.index')->assertSee('Voorlopig rapport genereren')
            ->assertViewHas('form', fn (array $form): bool => $form['temporary'] === true
                && $form['action'] === route('projects.reports.temporary.store', $fixture['project'])
                && $form['canEditParameters'] === false
                && $form['preferredTemplate'] === null
                && array_column($form['templates'], 'folder') === ['maz2020']
                && $form['assayOrder'] === [$fixture['assay']->id]);
    }

    #[DataProvider('unusableTemporaryTemplateSettings')]
    public function test_temporary_reports_offer_no_template_unless_the_cvar_lists_one(?string $value): void
    {
        $fixture = $this->fixture();

        if ($value !== null) {
            $this->allowTemporaryTemplates($value);
        }

        $this->get(route('projects.reports.temporary.index', $fixture['project']))->assertOk()
            ->assertViewHas('form', fn (array $form): bool => $form['templates'] === []);
        $this->post(route('projects.reports.temporary.store', $fixture['project']), $this->reportOptions($fixture))
            ->assertSessionHasErrors('templateSelect');
        $this->assertNotIssued($fixture['project']);
    }

    /** @return array<string, array{?string}> */
    public static function unusableTemporaryTemplateSettings(): array
    {
        return [
            'cvar missing' => [null],
            'blank value' => [''],
            'not json' => ['maz2020'],
            'json object without folders' => ['{"folder": 1}'],
            'other templates only' => ['["mazOverview", "mazOverviewEn"]'],
        ];
    }

    public function test_temporary_report_is_marked_as_such_and_withdraws_the_projects_authorisation(): void
    {
        $fixture = $this->fixture();
        $this->allowTemporaryTemplates();
        $portal = $this->fakePortal();
        $analysis = $fixture['analyses'][0];
        $analysis->update(['auth_status' => 1, 'auth_by' => $fixture['user']->id]);

        $response = $this->post(route('projects.reports.temporary.store', $fixture['project']), $this->reportOptions($fixture));

        $export = Export::query()->firstOrFail();
        $response->assertRedirect(route('projects.reports.show', [$fixture['project'], $export]))->assertSessionHasNoErrors();
        $this->assertDatabaseHas('exports', [
            'id' => $export->id, 'transaction' => $export->id, 'project' => $fixture['project']->id,
            'client' => $fixture['client']->id, 'revision' => 2, 'print_version' => 8,
            'type' => 2, 'temporary' => 1, 'was_authorized' => 1, 'template' => 'maz2020',
        ]);
        $this->assertSame(hash('sha256', $fixture['project']->id.'281'), $export->hash);
        Storage::disk('local')->assertExists($export->storagePath());
        $this->assertStringStartsWith('%PDF-', Storage::disk('local')->get($export->storagePath()));
        $this->get($response->headers->get('Location'))->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $project = $fixture['project']->fresh();
        $this->assertSame([0, 0, 3, 8, 0], [(int) $project->auth_status, (int) $project->auth_on, $project->revision, $project->print_version, (int) $project->rap_stat]);
        $this->assertNull($project->rap_by);
        $this->assertNull($project->rap_on);
        $this->assertSame(0, (int) $analysis->fresh()->auth_status);
        $this->assertNull($analysis->fresh()->storedResult);
        $revision = ChangeRevision::query()->where('type', '9')->sole();
        $this->assertSame([$fixture['project']->id, $fixture['user']->id, '1', '0'], [$revision->project, $revision->user_id, $revision->from, $revision->to]);
        $this->assertSame('Project authorisatie ingetrokken, bron wijziging: Voorlopig rapport, reden:Voorlopig rapport uitgegeven, autorisatie automatisch ingetrokken', $revision->event);
        $this->assertDatabaseMissing('changetracker', ['type' => '10']);
        Http::assertSentCount(1);
        Http::assertSent(fn (Request $request): bool => $request->url() === $portal.'projectDeauthorized'
            && collect($request->data())->firstWhere('name', 'preview')['contents'] === 'True');
    }

    public function test_temporary_report_ignores_parameter_selection_order_and_scope_from_the_request(): void
    {
        $fixture = $this->fixture(2);
        $this->allowTemporaryTemplates();
        $this->fakePortal();
        $second = $this->assay(['custom_fields' => json_encode(['raportnaam' => 'Andere analyse', 'accred' => '', 'resultin' => 'kve/g'])]);
        $fixture['template']->assays()->attach($second->id, ['original_assay_id' => $second->original_id]);
        $this->analysis($fixture['project'], $fixture['samples']->first(), $second, $fixture['source'])->update(['project_order' => 2]);
        $captured = [];
        $build = Mockery::mock(BuildProjectReport::class, [app(CalculateAnalysisResult::class)])->makePartial();
        $build->shouldReceive('handle')->once()->withArgs(function (Project $project, Collection $samples, array $options) use (&$captured): bool {
            $captured = ['samples' => $samples->modelKeys(), 'options' => $options];

            return true;
        })->passthru();
        $this->app->instance(BuildProjectReport::class, $build);

        $this->post(route('projects.reports.temporary.store', $fixture['project']), $this->reportOptions($fixture, [
            'PDFtypeSelect' => 0, 'reportType' => 'projectSample', 'exportSample' => [$fixture['samples']->first()->id],
            'assay_ids' => [$second->id], 'assay_order' => [$second->id, $fixture['assay']->id],
        ]))->assertRedirect()->assertSessionHasNoErrors();

        $everyAssay = [$fixture['assay']->id, $second->id];
        $this->assertSame($fixture['samples']->modelKeys(), $captured['samples']);
        $this->assertSame(
            ['projectReport', 1, $everyAssay, $everyAssay],
            [$captured['options']['reportType'], $captured['options']['PDFtypeSelect'], $captured['options']['assay_ids'], $captured['options']['assay_order']],
        );
        $export = Export::query()->sole();
        $this->assertSame([2, 1], [(int) $export->type, (int) $export->temporary]);
        $this->assertSame($fixture['samples']->modelKeys(), $export->sample_id_scope);
    }

    public function test_temporary_report_cannot_use_a_template_outside_the_allowed_list(): void
    {
        $fixture = $this->fixture();
        $this->allowTemporaryTemplates('["legacyOverview"]');
        $this->fakePortal();

        $this->postJson(route('projects.reports.temporary.store', $fixture['project']), $this->reportOptions($fixture))
            ->assertUnprocessable()->assertJsonValidationErrors('templateSelect');

        $this->assertNotIssued($fixture['project']);
        $this->assertSame(1, (int) $fixture['project']->fresh()->auth_status);
        Http::assertNothingSent();
    }

    public function test_temporary_report_without_reportable_output_keeps_the_project_authorised(): void
    {
        $fixture = $this->fixture();
        $this->allowTemporaryTemplates();
        $this->fakePortal();
        $fixture['assay']->update(['hide_report' => 1]);

        $this->postJson(route('projects.reports.temporary.store', $fixture['project']), $this->reportOptions($fixture))
            ->assertUnprocessable()->assertJsonValidationErrors('report');

        $this->assertNotIssued($fixture['project']);
        $this->assertSame(1, (int) $fixture['project']->fresh()->auth_status);
        Http::assertNothingSent();
    }

    public function test_temporary_preview_changes_nothing_and_asks_for_the_temporary_watermark(): void
    {
        $fixture = $this->fixture();
        $this->allowTemporaryTemplates();
        $this->fakePortal();
        $before = $fixture['project']->fresh()->only(['auth_status', 'print_version', 'rap_stat', 'rap_on', 'rap_by', 'revision']);
        $rendered = [];
        $render = Mockery::mock(RenderReportPdf::class)->makePartial();
        $render->shouldReceive('handle')->once()->withArgs(function (array $reports, bool $preview, bool $temporary) use (&$rendered): bool {
            $rendered = [$preview, $temporary];

            return true;
        })->passthru();
        $this->app->instance(RenderReportPdf::class, $render);

        $response = $this->post(route('projects.reports.temporary.store', $fixture['project']), $this->reportOptions($fixture, ['reportExportType' => 'preview']));

        $response->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringStartsWith('%PDF-', $response->getContent());
        $this->assertSame([true, true], $rendered);
        $this->assertSame($before, $fixture['project']->fresh()->only(array_keys($before)));
        $this->assertDatabaseCount('exports', 0);
        $this->assertDatabaseCount('changetracker', 0);
        Http::assertNothingSent();
    }

    #[DataProvider('reportWatermarks')]
    public function test_watermarks_distinguish_previews_and_temporary_previews(bool $authorized, bool $preview, bool $temporary, string $expected): void
    {
        $this->assertSame($expected, app(RenderReportPdf::class)->watermark($authorized, $preview, $temporary));
    }

    /** @return array<string, array{bool, bool, bool, string}> */
    public static function reportWatermarks(): array
    {
        return [
            'authorised report' => [true, false, false, ''],
            'unauthorised report' => [false, false, false, 'NIET GEAUTHORISEERD'],
            'authorised preview' => [true, true, false, 'VOORBEELD - NIET VOOR DISTRIBUTIE'],
            'unauthorised preview' => [false, true, false, 'VOORBEELD - NIET GEAUTHORISEERD'],
            'authorised temporary report' => [true, false, true, ''],
            'unauthorised temporary report' => [false, false, true, 'NIET GEAUTHORISEERD'],
            'authorised temporary preview' => [true, true, true, 'TIJDELIJK RAPPORT VOORBEELD - NIET VOOR DISTRIBUTIE'],
            'unauthorised temporary preview' => [false, true, true, 'VOORLOPIG RAPPORT VOORBEELD - NIET GEAUTHORISEERD'],
        ];
    }

    public function test_temporary_report_for_an_unauthorised_project_is_marked_temporary_without_any_authorisation_side_effects(): void
    {
        $fixture = $this->fixture();
        $fixture['project']->update(['auth_status' => 0, 'auth_by' => null]);
        $this->allowTemporaryTemplates();
        $this->fakePortal();

        $this->post(route('projects.reports.temporary.store', $fixture['project']), $this->reportOptions($fixture))
            ->assertRedirect()->assertSessionHasNoErrors();

        $this->assertDatabaseHas('exports', ['project' => $fixture['project']->id, 'type' => 2, 'temporary' => 1, 'was_authorized' => 0, 'print_version' => 8]);
        $this->assertDatabaseHas('projects', ['id' => $fixture['project']->id, 'auth_status' => 0, 'revision' => 2, 'print_version' => 7, 'rap_stat' => 0]);
        $this->assertDatabaseCount('changetracker', 0);
        Http::assertNothingSent();
    }

    public function test_a_portal_outage_does_not_undo_an_issued_temporary_report(): void
    {
        $fixture = $this->fixture();
        $this->allowTemporaryTemplates();
        config(['services.glass.base_url' => 'http://portal.test/api']);
        Http::fake(fn () => throw new ConnectionException('Portal offline'));

        $this->post(route('projects.reports.temporary.store', $fixture['project']), $this->reportOptions($fixture))
            ->assertRedirect()->assertSessionHasNoErrors();

        $this->assertDatabaseHas('exports', ['project' => $fixture['project']->id, 'type' => 2, 'temporary' => 1]);
        $this->assertDatabaseHas('projects', ['id' => $fixture['project']->id, 'auth_status' => 0, 'revision' => 3]);
    }

    public function test_temporary_report_of_an_authorised_project_shows_unfinished_analyses_as_not_completed(): void
    {
        $fixture = $this->fixture();
        $this->allowTemporaryTemplates();
        $this->fakePortal();
        $second = $this->assay(['custom_fields' => json_encode(['raportnaam' => 'Andere analyse', 'accred' => '', 'resultin' => 'kve/g'])]);
        $fixture['template']->assays()->attach($second->id, ['original_assay_id' => $second->original_id]);
        $unfinished = $this->analysis($fixture['project'], $fixture['samples']->first(), $second, $fixture['source']);
        $unfinished->update(['is_ready' => false, 'storedResult' => null, 'project_order' => 2]);
        $unfinished->results->first()->update(['data' => ['kve' => '']]);
        $fixture['project']->update(['auth_status' => 0, 'auth_by' => null]);
        $authorised = app(AuthorizeProject::class)->handle($fixture['project'], $fixture['user']->id, true, true);
        $this->assertTrue($authorised['authorized']);
        $rendered = [];
        $render = Mockery::mock(RenderReportPdf::class)->makePartial();
        $render->shouldReceive('handle')->once()->withArgs(function (array $reports) use (&$rendered): bool {
            $rendered = $reports[0]['samples'][0]['results'];

            return true;
        })->passthru();
        $this->app->instance(RenderReportPdf::class, $render);

        $this->post(route('projects.reports.temporary.store', $fixture['project']), $this->reportOptions($fixture, [
            'assay_ids' => [$fixture['assay']->id, $second->id], 'assay_order' => [$fixture['assay']->id, $second->id],
        ]))->assertRedirect()->assertSessionHasNoErrors();

        $this->assertSame('Aangetoond', (string) $rendered[$fixture['assay']->id]['output']);
        $this->assertSame('Niet afgerond', (string) $rendered[$second->id]['output']);
        $this->assertDatabaseHas('exports', ['project' => $fixture['project']->id, 'type' => 2, 'temporary' => 1, 'was_authorized' => 1]);
        $this->assertDatabaseHas('projects', ['id' => $fixture['project']->id, 'auth_status' => 0, 'revision' => 3]);
        $this->assertNull($unfinished->fresh()->storedResult);
    }

    /** @return array<string, mixed> */
    private function fixture(int $sampleCount = 1): array
    {
        $this->travelTo(Carbon::parse('2026-10-24 12:00:00', 'Europe/Amsterdam'));
        Storage::fake('local');
        $user = User::factory()->create(['enabled' => 1]);
        $this->actingAs($user);
        Gate::define('projects.view', fn (): bool => true);
        Gate::define('reports.generate', fn (): bool => true);
        Gate::define('reports.parameters.update', fn (): bool => true);
        $image = UploadedFile::fake()->image('signature.png', 120, 40);
        Storage::disk('local')->put(AccountImages::signaturePath($user, 'nl'), file_get_contents($image->path()));
        $client = Client::query()->create([
            'name' => 'Voorbeeld & Co', 'title' => 'Dhr.', 'fname' => 'Jan', 'lname' => 'Jansen',
            'street_name' => 'Laboratoriumweg', 'street_number' => '1', 'postal_code' => '1234 AB',
            'place' => 'Kloosterzande', 'country' => 'Nederland', 'trip_red' => 1, 'report_notes' => 'Controleer de rapportinstellingen.',
        ]);
        $project = Project::query()->create([
            'client' => $client->id, 'subclient' => 0, 'reference' => '261024', 'project_name' => 'Klantreferentie',
            'revision' => 2, 'print_version' => 7, 'auth_status' => 1, 'auth_by' => $user->id,
            'is_ready' => 1, 'started' => 1, 'project_notes' => '{"project":"Algemene opmerking."}',
            'custom_fields' => '{"project_monster":"23-10-2026","project_ontvangst":"24-10-2026"}',
        ]);
        $procedure = SampleProcedure::query()->create(['name' => 'Bemonstering', 'active' => 1, 'hide' => 0, 'fields' => '{"door":"Opdrachtgever","accred":"","conform":"Eigen methode","referentiemethode":"NEN-EN ISO 7218"}']);
        $assay = $this->assay();
        $source = ReferenceSource::query()->create(['name' => ['nl' => 'Interne norm', 'en' => 'Internal standard']]);
        $template = DocgenTemplate::factory()->create();
        $template->assays()->attach($assay->id, ['original_assay_id' => $assay->original_id]);
        Footer::factory()->create(['q' => 0, 'data' => 'Goedgekeurde algemene certificaattekst.']);
        Footer::factory()->create(['q' => 1, 'data' => 'Goedgekeurde RvA certificaattekst.']);
        $samples = new Collection;
        $analyses = [];

        for ($index = 1; $index <= $sampleCount; $index++) {
            $sample = $this->sample($project, $index, $procedure->id);
            $samples->push($sample);
            $analyses[] = $this->analysis($project, $sample, $assay, $source);
        }

        return compact('user', 'client', 'project', 'procedure', 'assay', 'source', 'template', 'samples', 'analyses');
    }

    private function sample(Project $project, int $index, int $procedure = 0): Sample
    {
        $sample = $project->samples()->create([
            'barcode' => '261024'.sprintf('%03d', $index), 'follow_no' => $index,
            'description' => 'Monster '.$index, 'client_description' => 'Monster '.$index, 'sampling_method' => $procedure,
            'date_registered' => (string) now()->timestamp, 'registered_by' => 1, 'client' => $project->getRawOriginal('client'),
            'subclient' => 0, 'custom_fields' => json_encode(['details' => 'Partij '.$index]),
            'predicted_end' => -1, 'sample_innoculated' => (string) now()->timestamp,
            'analyses_data' => '[]', 'sample_extra' => '{"location":"Koelcel 1"}', 'source' => 1,
        ]);
        $sample->metadata()->create(['name' => 'Temperatuur', 'value' => '4 C', 'meta_order' => 1]);

        return $sample;
    }

    private function assay(array $overrides = []): Assay
    {
        return Assay::query()->create([
            'original_id' => 1, 'name' => 'Testanalyse', 'type_base' => 1, 'media_id' => '[]',
            'dillution' => 0, 'replicates' => 0, 'confirmation' => 0, 'confirmation_script' => '{}',
            'type' => 1, 'meta_assays' => '', 'max_count' => 300, 'min_count' => 10, 'script' => 'file:maz7218grens',
            'custom_fields' => '{"raportnaam":"Totaal kiemgetal","accred":"Q","resultin":"kve/g","techniek":"Gietplaat","conform":"Conform","referentiemethode":"NEN-EN ISO 4833"}',
            'duration' => '48', 'start_from' => '', 'article_code' => '', ...$overrides,
        ])->fresh();
    }

    private function analysis(Project $project, Sample $sample, Assay $assay, ReferenceSource $source): SampleAnalysis
    {
        $analysis = $sample->analyses()->create([
            'profile_group' => 1, 'follow_number' => 1, 'profile' => 0, 'assay' => $assay->id,
            'assay_base' => $assay->id, 'original_assay_base' => $assay->original_id,
            'predicted_end' => -1, 'project' => $project->id, 'project_order' => 1, 'is_ready' => true,
            'storedResult' => ['output' => ['kve' => '1200'], 'outputEn' => ['kve' => '1200'], 'reportIn' => 'kve', 'disposition' => ['kve' => '+'], 'isReady' => true, 'resultMask' => ['kve' => 'kve'], 'resultHide' => []],
        ]);
        $settings = $analysis->roamingSettings()->create(['assay' => $assay->id, 'dillutions' => [1], 'replicates' => 0, 'reference' => ['ref_kve' => '1000'], 'reference_source' => $source->id]);
        $analysis->roamingAnalysis()->associate($settings);
        $analysis->save();
        $analysis->results()->create([
            'sample' => $sample->id, 'follow_no' => 1, 'profile' => 0, 'assay' => $assay->id,
            'assay_base' => $assay->id, 'roaming_id' => $settings->id, 'df' => '1', 'rep' => 0, 'data' => ['kve' => '+'],
        ]);

        return $analysis;
    }

    /** @return array<string, mixed> */
    private function reportOptions(array $fixture, array $overrides = []): array
    {
        return [
            'templateSelect' => 'maz2020', 'PDFtypeSelect' => 1, 'reportType' => 'projectReport',
            'reportExportType' => 'create', 'noSamples' => 5, 'showViolation' => 1, 'languageSelect' => 'nl',
            'assay_ids' => [$fixture['assay']->id], 'assay_order' => [$fixture['assay']->id], ...$overrides,
        ];
    }

    private function assertNotIssued(Project $project): void
    {
        $this->assertDatabaseHas('projects', ['id' => $project->id, 'print_version' => 7, 'rap_stat' => 0, 'revision' => 2]);
        $this->assertDatabaseCount('exports', 0);
        $this->assertDatabaseCount('changetracker', 0);
        $this->assertSame([], Storage::disk('local')->files('reportStorage'));
    }

    private function allowTemporaryTemplates(string $folders = '["maz2020"]'): void
    {
        Cvar::query()->create(['cvar' => 'DOCGEN_TEMPLATES_ALLOWED_TEMP', 'value' => $folders, 'default' => '', 'description' => 'Allowed templates for temp certificates']);
    }

    /** Fakes the client portal and returns its base URL, ending in a slash. */
    private function fakePortal(): string
    {
        config(['services.glass.base_url' => 'http://portal.test/api']);
        Http::preventStrayRequests();
        Http::fake(['http://portal.test/api/*' => Http::response(['ok' => true])]);

        return 'http://portal.test/api/';
    }
}
