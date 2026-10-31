<?php

namespace Tests\Feature;

use App\Actions\Results\CalculateAnalysisResult;
use App\Actions\Results\GetAnalysisResults;
use App\Actions\Results\UpdateResultValue;
use App\Events\AnalysisResultCalculated;
use App\Jobs\CalculateAnalysisResultJob;
use App\Models\Assay;
use App\Models\Project;
use App\Models\Sample;
use App\Models\SampleAnalysis;
use App\Models\User;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class VetoResultTest extends TestCase
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

    public function test_setting_a_veto_preserves_legacy_storage_and_audits_the_sample_and_analysis(): void
    {
        $this->operator();
        $this->freezeTime();
        $analysis = $this->analysis();
        Queue::fake([CalculateAnalysisResultJob::class]);

        $this->putJson($this->endpoint($analysis), $this->payload())
            ->assertOk()
            ->assertJsonPath('data.calculation.output.kve', 'Niet te bepalen')
            ->assertJsonPath('data.calculation.outputEn.kve', 'Niet te bepalen')
            ->assertJsonPath('data.calculation.vetoOverWrite.kve', 'Aangetoond')
            ->assertJsonPath("data.calculation.vetoReasons.{$analysis->id}.kve", 'Controle uitgevoerd')
            ->assertJsonPath('data.calculation.disposition.kve', '-')
            ->assertJsonPath('data.calculation.isReady', true);

        $this->assertDatabaseHas('vetoresults', [
            'said' => $analysis->id, 'parameter' => 'kve', 'result' => 'Niet te bepalen',
            'reason' => 'Controle uitgevoerd', 'disposition' => null,
        ]);
        $this->assertDatabaseHas('changetracker', [
            'type' => '11', 'project' => $analysis->project, 'sample' => $analysis->sample,
            'said' => $analysis->id, 'user_id' => auth()->id(),
            'event' => 'Veto resultaat aangepast voor monster VETO1 in analyse Testanalyse. Reden: Controle uitgevoerd',
            'from' => 'kve:Aangetoond', 'to' => 'kve:Niet te bepalen',
        ]);
        $this->assertDatabaseHas('projects', ['id' => $analysis->project, 'last_edit' => now()->timestamp]);
        $this->assertSame('Niet te bepalen', $analysis->fresh()->storedResult['output']['kve']);
        $this->assertTrue($analysis->fresh()->has_veto_result);
        $this->assertSame($analysis->id, $analysis->vetoResults()->firstOrFail()->sampleAnalysis->id);
        Queue::assertPushed(CalculateAnalysisResultJob::class, fn ($job) => $job->analysisId === $analysis->id);
        Queue::assertPushed(CalculateAnalysisResultJob::class, 1);
    }

    #[DataProvider('overrideValues')]
    public function test_veto_values_and_dispositions_follow_the_legacy_output_contract(string $value, ?string $disposition, string $expected): void
    {
        $this->operator();
        $analysis = $this->analysis();
        Queue::fake([CalculateAnalysisResultJob::class]);

        $this->putJson($this->endpoint($analysis), $this->payload(['value' => $value, 'disposition' => $disposition]))
            ->assertOk()
            ->assertJsonPath('data.calculation.output.kve', $value)
            ->assertJsonPath('data.calculation.outputEn.kve', $value)
            ->assertJsonPath('data.calculation.disposition.kve', $expected);

        $this->assertDatabaseHas('vetoresults', ['said' => $analysis->id, 'result' => $value, 'disposition' => $disposition]);
        Queue::assertPushed(CalculateAnalysisResultJob::class, 1);
    }

    public static function overrideValues(): array
    {
        return [
            'zero is an explicit override' => ['0', null, '+'],
            'numeric retains calculated disposition' => ['12.5', null, '+'],
            'text without disposition becomes good' => ['n.t.b.', null, '-'],
            'explicit positive overrides text default' => ['Afwijkend', '+', '+'],
            'explicit good' => ['0', '-', '-'],
            'explicit neutral' => ['Onbekend', '.', '.'],
        ];
    }

    public function test_legacy_512_character_limits_are_accepted_without_truncation(): void
    {
        $this->operator();
        $analysis = $this->analysis();
        $value = str_repeat('v', 512);
        $reason = str_repeat('r', 512);
        Queue::fake([CalculateAnalysisResultJob::class]);

        $this->putJson($this->endpoint($analysis), $this->payload(['value' => $value, 'reason' => $reason]))
            ->assertOk()->assertJsonPath('data.calculation.output.kve', $value);

        $this->assertDatabaseHas('vetoresults', ['said' => $analysis->id, 'result' => $value, 'reason' => $reason]);
        Queue::assertPushed(CalculateAnalysisResultJob::class, 1);
    }

    public function test_veto_job_uniqueness_is_acquired_only_after_the_outer_transaction_commits(): void
    {
        $this->operator();
        $analysis = $this->analysis();
        config(['cache.default' => 'database']);
        Queue::fake([CalculateAnalysisResultJob::class]);

        DB::transaction(function () use ($analysis): void {
            $this->putJson($this->endpoint($analysis), $this->payload())->assertOk();

            $this->assertDatabaseCount('cache_locks', 0);
            Queue::assertNothingPushed();
        });

        $this->assertDatabaseHas('vetoresults', ['said' => $analysis->id, 'result' => 'Niet te bepalen']);
        $this->assertDatabaseCount('cache_locks', 1);
        Queue::assertPushed(CalculateAnalysisResultJob::class, 1);
    }

    #[DataProvider('sampleChannelAccess')]
    public function test_existing_sample_channel_allows_project_lookup_viewers_without_requiring_veto_permission(bool $sampleAccess, bool $projectAccess, bool $enabled, bool $expected): void
    {
        $user = User::factory()->create(['enabled' => $enabled]);
        Gate::define('samples.view', fn () => $sampleAccess);
        Gate::define('projects.view', fn () => $projectAccess);
        Gate::define('results.veto', fn () => false);
        $analysis = $this->analysis();
        $authorize = Broadcast::getChannels()->get('samples.{sampleId}');

        $this->assertSame($expected, $authorize($user, $analysis->sample));
        $this->assertFalse($authorize($user, 99999));
    }

    public static function sampleChannelAccess(): array
    {
        return [
            'sample reader' => [true, false, true, true],
            'project reader' => [false, true, true, true],
            'neither permission' => [false, false, true, false],
            'disabled reader' => [true, true, false, false],
        ];
    }

    public function test_editing_reuses_the_veto_row_and_logs_the_previous_effective_result(): void
    {
        $this->operator();
        $analysis = $this->analysis();
        $veto = $analysis->vetoResults()->create([
            'parameter' => 'kve', 'result' => 'Oud', 'reason' => 'Legacy reden', 'disposition' => '.',
        ]);
        Queue::fake([CalculateAnalysisResultJob::class]);

        $this->putJson($this->endpoint($analysis), $this->payload(['value' => 'Nieuw', 'reason' => 'Nieuwe controle', 'disposition' => '+']))
            ->assertOk()->assertJsonPath('data.calculation.vetoOverWrite.kve', 'Aangetoond');

        $this->assertDatabaseCount('vetoresults', 1);
        $this->assertDatabaseHas('vetoresults', ['id' => $veto->id, 'result' => 'Nieuw', 'reason' => 'Nieuwe controle', 'disposition' => '+']);
        $this->assertDatabaseHas('changetracker', ['said' => $analysis->id, 'from' => 'kve:Oud', 'to' => 'kve:Nieuw']);
        Queue::assertPushed(CalculateAnalysisResultJob::class, 1);
    }

    public function test_recalculation_keeps_the_veto_and_removal_restores_the_latest_automatic_result(): void
    {
        $this->operator();
        $analysis = $this->analysis();
        $analysis->vetoResults()->create(['parameter' => 'kve', 'result' => 'Override', 'reason' => 'Controle', 'disposition' => '.']);
        app(CalculateAnalysisResult::class)->handle($analysis);
        app(UpdateResultValue::class)->handle($analysis, $analysis->results()->firstOrFail(), 'kve', '-');
        $calculation = app(CalculateAnalysisResult::class)->handle($analysis);
        $this->assertSame('Override', $calculation['output']['kve']);
        $this->assertSame('Niet aangetoond', $calculation['vetoOverWrite']['kve']);
        Queue::fake([CalculateAnalysisResultJob::class]);

        $this->deleteJson($this->endpoint($analysis, 'destroy'), ['parameter' => 'kve', 'reason' => 'Hercontrole afgerond'])
            ->assertOk()
            ->assertJsonPath('data.calculation.output.kve', 'Niet aangetoond')
            ->assertJsonPath('data.calculation.outputEn.kve', 'Not detected')
            ->assertJsonPath('data.calculation.disposition.kve', '-')
            ->assertJsonPath('data.veto_results', [])
            ->assertJsonMissingPath('data.calculation.vetoOverWrite');

        $this->assertDatabaseMissing('vetoresults', ['said' => $analysis->id]);
        $this->assertFalse($analysis->fresh()->has_veto_result);
        $this->assertDatabaseHas('changetracker', [
            'type' => '11', 'project' => $analysis->project, 'sample' => $analysis->sample, 'said' => $analysis->id,
            'event' => 'Veto resultaat verwijderd voor monster VETO1 in analyse Testanalyse. Reden: Hercontrole afgerond',
            'from' => 'kve:Override', 'to' => 'kve:Niet aangetoond',
        ]);
        Queue::assertPushed(CalculateAnalysisResultJob::class, 1);
    }

    #[DataProvider('invalidInputs')]
    public function test_invalid_veto_changes_return_validation_errors_without_changing_results(array $payload, string $field): void
    {
        $this->operator();
        $analysis = $this->analysis();
        Queue::fake();

        $this->putJson($this->endpoint($analysis), $payload)->assertUnprocessable()->assertJsonValidationErrors($field);

        $this->assertDatabaseCount('vetoresults', 0);
        $this->assertDatabaseCount('changetracker', 0);
        $this->assertNull($analysis->fresh()->storedResult);
        Queue::assertNothingPushed();
    }

    public static function invalidInputs(): array
    {
        $valid = ['parameter' => 'kve', 'value' => '0', 'reason' => 'Controle'];

        return [
            'missing reason' => [array_diff_key($valid, ['reason' => true]), 'reason'],
            'blank reason' => [array_replace($valid, ['reason' => '  ']), 'reason'],
            'reason too long' => [array_replace($valid, ['reason' => str_repeat('a', 513)]), 'reason'],
            'blank result' => [array_replace($valid, ['value' => '']), 'value'],
            'array result' => [array_replace($valid, ['value' => ['bad']]), 'value'],
            'result too long' => [array_replace($valid, ['value' => str_repeat('a', 513)]), 'value'],
            'unknown parameter' => [array_replace($valid, ['parameter' => 'invented']), 'parameter'],
            'missing parameter' => [array_diff_key($valid, ['parameter' => true]), 'parameter'],
            'parameter too long' => [array_replace($valid, ['parameter' => str_repeat('a', 513)]), 'parameter'],
            'invalid disposition' => [array_replace($valid, ['disposition' => 'bad']), 'disposition'],
        ];
    }

    #[DataProvider('dispositionRequiredTypes')]
    public function test_border_and_meta_analyses_require_an_explicit_disposition(int $type): void
    {
        $this->operator();
        $analysis = $this->analysis();
        $analysis->assayRecord->update(['type' => $type]);
        Queue::fake();

        $this->putJson($this->endpoint($analysis), $this->payload())
            ->assertUnprocessable()->assertJsonPath('errors.disposition.0', 'Selecteer een uitslagtype voor deze analyse.');

        $this->assertDatabaseCount('vetoresults', 0);
        Queue::assertNothingPushed();
    }

    public static function dispositionRequiredTypes(): array
    {
        return ['border' => [2], 'meta' => [4]];
    }

    public function test_removal_requires_a_new_nonblank_reason_and_does_not_reuse_the_saved_reason(): void
    {
        $this->operator();
        $analysis = $this->analysis();
        $veto = $analysis->vetoResults()->create(['parameter' => 'kve', 'result' => '0', 'reason' => 'Oorspronkelijke reden']);
        Queue::fake();

        $this->deleteJson($this->endpoint($analysis, 'destroy'), ['parameter' => 'kve', 'reason' => '  '])
            ->assertUnprocessable()->assertJsonPath('errors.reason.0', 'Een reden voor deze wijziging is verplicht.');

        $this->assertModelExists($veto);
        $this->assertDatabaseCount('changetracker', 0);
        Queue::assertNothingPushed();
    }

    #[DataProvider('forbiddenChanges')]
    public function test_veto_changes_enforce_permissions_and_project_state(string $method, bool $view, bool $vetoPermission, int $authorized, int $locked, int $status): void
    {
        $this->operator();
        Gate::define('projects.view', fn () => $view);
        Gate::define('results.veto', fn () => $vetoPermission);
        $analysis = $this->analysis();
        $analysis->projectRecord->update(['auth_status' => $authorized, 'locked' => $locked]);
        $veto = $analysis->vetoResults()->create(['parameter' => 'kve', 'result' => 'Oud', 'reason' => 'Controle']);
        Queue::fake();

        $this->json($method, $this->endpoint($analysis), $this->payload())->assertStatus($status);

        $this->assertSame('Oud', $veto->fresh()->result);
        $this->assertDatabaseCount('changetracker', 0);
        Queue::assertNothingPushed();
    }

    public static function forbiddenChanges(): array
    {
        return [
            'save without veto permission' => ['PUT', true, false, 0, 0, 403],
            'remove without veto permission' => ['DELETE', true, false, 0, 0, 403],
            'save without project access' => ['PUT', false, true, 0, 0, 403],
            'remove without project access' => ['DELETE', false, true, 0, 0, 403],
            'save authorized' => ['PUT', true, true, 1, 0, 423],
            'remove authorized' => ['DELETE', true, true, 1, 0, 423],
            'save locked' => ['PUT', true, true, 0, 1, 423],
            'remove locked' => ['DELETE', true, true, 0, 1, 423],
        ];
    }

    public function test_guests_cannot_change_vetoes(): void
    {
        $analysis = $this->analysis();

        $this->putJson($this->endpoint($analysis), $this->payload())->assertUnauthorized();
        $this->deleteJson($this->endpoint($analysis, 'destroy'), $this->payload())->assertUnauthorized();

        $this->assertDatabaseCount('vetoresults', 0);
    }

    public function test_nested_routes_reject_a_different_project_or_sample(): void
    {
        $this->operator();
        $analysis = $this->analysis();
        $other = $this->analysis();
        Queue::fake();

        foreach ([
            ['project' => $other->project, 'sample' => $analysis->sample],
            ['project' => $other->project, 'sample' => $other->sample],
        ] as $parents) {
            $url = route('projects.search.samples.veto.update', [...$parents, 'analysis' => $analysis->id]);
            $this->putJson($url, $this->payload())->assertNotFound();
            $this->deleteJson($url, $this->payload())->assertNotFound();
        }

        $this->assertDatabaseCount('vetoresults', 0);
        Queue::assertNothingPushed();
    }

    public function test_imported_vetoes_are_applied_to_authorized_snapshots_and_shared_result_reads(): void
    {
        $this->operator();
        $analysis = $this->analysis();
        $automatic = app(CalculateAnalysisResult::class)->handle($analysis);
        $analysis->projectRecord->update(['auth_status' => 1]);
        DB::table('vetoresults')->insert([
            'id' => 123, 'said' => $analysis->id, 'parameter' => 'kve',
            'result' => 'Legacy override', 'reason' => 'Legacy reden', 'disposition' => '.',
        ]);
        Queue::fake();

        $calculated = app(CalculateAnalysisResult::class)->handle($analysis);
        $read = app(GetAnalysisResults::class)->handle($analysis);

        $this->assertSame('Legacy override', $calculated['output']['kve']);
        $this->assertSame('Aangetoond', $calculated['vetoOverWrite']['kve']);
        $this->assertSame($calculated, $read['calculation']);
        $this->assertSame($automatic, $analysis->fresh()->storedResult);
        $this->assertFalse($read['calculation_queued']);
        Queue::assertNothingPushed();
    }

    public function test_authorization_stores_effective_results_before_notifying_the_portal(): void
    {
        $this->operator();
        Gate::define('projects.authorisation.manage', fn () => true);
        $analysis = $this->analysis();
        $analysis->vetoResults()->create(['parameter' => 'kve', 'result' => '0', 'reason' => 'Controle', 'disposition' => '-']);
        app(CalculateAnalysisResult::class)->handle($analysis);
        config(['services.glass.base_url' => 'http://portal.test/api']);
        Http::preventStrayRequests();
        $portalUrl = 'http://portal.test/api/projectAuthorized/'.$analysis->project;
        Http::fake([$portalUrl => function () use ($analysis) {
            $this->assertSame('0', $analysis->fresh()->storedResult['output']['kve']);
            $this->assertSame('0', $analysis->fresh()->storedResult['outputEn']['kve']);

            return Http::response(['ok' => true]);
        }]);

        $this->postJson(route('projects.authorization.store', ['project' => $analysis->project]), ['quiet' => true])
            ->assertOk()->assertJsonPath('data.authorized', true)->assertJsonPath('data.portal_sync_failed', false);

        $this->assertDatabaseHas('projects', ['id' => $analysis->project, 'auth_status' => 1]);
        $this->assertDatabaseHas('vetoresults', ['said' => $analysis->id, 'result' => '0']);
        Http::assertSent(fn ($request) => $request->url() === $portalUrl && $request->method() === 'POST');
        Http::assertSentCount(1);
    }

    public function test_importing_a_veto_invalidates_an_unauthorized_cached_result_and_jobs_broadcast_it(): void
    {
        $analysis = $this->analysis();
        app(CalculateAnalysisResult::class)->handle($analysis);
        $analysis->vetoResults()->create(['parameter' => 'kve', 'result' => 'Override', 'reason' => 'Controle']);
        Queue::fake([CalculateAnalysisResultJob::class]);
        Event::fake([AnalysisResultCalculated::class]);

        $read = app(GetAnalysisResults::class)->handle($analysis);
        $this->assertNull($read['calculation']);
        $this->assertTrue($read['calculation_queued']);
        Queue::assertPushed(CalculateAnalysisResultJob::class, 1);
        (new CalculateAnalysisResultJob($analysis->id))->handle(app(CalculateAnalysisResult::class));

        Event::assertDispatched(AnalysisResultCalculated::class, fn ($event) => $event->analysisId === $analysis->id
            && $event->calculation['output']['kve'] === 'Override'
            && $event->calculation['outputEn']['kve'] === 'Override');
        $read = app(GetAnalysisResults::class)->handle($analysis);
        $this->assertSame('Override', $read['calculation']['output']['kve']);
        $this->assertSame('Aangetoond', $read['calculation']['vetoOverWrite']['kve']);
        $this->assertFalse($read['calculation_queued']);
    }

    public function test_veto_does_not_mark_an_incomplete_analysis_ready(): void
    {
        $this->operator();
        $analysis = $this->analysis();
        $analysis->results()->firstOrFail()->update(['data' => ['kve' => '']]);
        Queue::fake([CalculateAnalysisResultJob::class]);

        $this->putJson($this->endpoint($analysis), $this->payload())
            ->assertOk()->assertJsonPath('data.calculation.output.kve', 'Niet te bepalen')
            ->assertJsonPath('data.calculation.isReady', false);

        $this->assertFalse($analysis->fresh()->is_ready);
        Queue::assertPushed(CalculateAnalysisResultJob::class, 1);
    }

    public function test_exact_legacy_output_keys_are_preserved_and_other_parameters_are_not_overridden(): void
    {
        $this->operator();
        $analysis = $this->analysis();
        $analysis->assayRecord->update(['script' => "file:maz7218grens\n\$analyseIsPCR=true\n\$bijPositiefNietOpRapport=true"]);
        Queue::fake([CalculateAnalysisResultJob::class]);

        $this->putJson($this->endpoint($analysis), $this->payload(['parameter' => 'kve ', 'value' => 'Beoordeeld']))
            ->assertOk()
            ->assertJsonPath('data.calculation.output.kve ', 'Beoordeeld')
            ->assertJsonPath('data.calculation.output.kve', '');

        $this->assertDatabaseHas('vetoresults', ['said' => $analysis->id, 'parameter' => 'kve ', 'result' => 'Beoordeeld']);
        Queue::assertPushed(CalculateAnalysisResultJob::class, 1);
    }

    public function test_removing_a_missing_veto_returns_404_without_a_spurious_audit_entry(): void
    {
        $this->operator();
        $analysis = $this->analysis();
        Queue::fake();

        $this->deleteJson($this->endpoint($analysis, 'destroy'), ['parameter' => 'kve', 'reason' => 'Hercontrole'])
            ->assertNotFound();

        $this->assertDatabaseCount('changetracker', 0);
        $this->assertNull($analysis->fresh()->storedResult);
        Queue::assertNothingPushed();
    }

    private function operator(): void
    {
        $this->actingAs(User::factory()->create(['enabled' => 1]));
        Gate::define('projects.view', fn () => true);
        Gate::define('results.veto', fn () => true);
    }

    private function endpoint(SampleAnalysis $analysis, string $action = 'update'): string
    {
        return route('projects.search.samples.veto.'.$action, [
            'project' => $analysis->project, 'sample' => $analysis->sample, 'analysis' => $analysis->id,
        ]);
    }

    private function payload(array $overrides = []): array
    {
        return array_replace(['parameter' => 'kve', 'value' => 'Niet te bepalen', 'reason' => 'Controle uitgevoerd'], $overrides);
    }

    private function analysis(): SampleAnalysis
    {
        $project = Project::create(['client' => 1, 'subclient' => 0, 'project_name' => 'Veto project']);
        $sample = Sample::create([
            'barcode' => 'VETO'.$project->id, 'follow_no' => 1, 'description' => 'Veto sample', 'sampling_method' => 1,
            'date_registered' => '1791590400', 'registered_by' => 1, 'client' => 1, 'subclient' => 0,
            'project' => $project->id, 'custom_fields' => '{}', 'predicted_end' => -1,
            'sample_innoculated' => '1791590400', 'analyses_data' => '[]',
        ]);
        $assay = Assay::create([
            'original_id' => 1, 'name' => 'Testanalyse', 'type_base' => 1, 'media_id' => '[]',
            'dillution' => 0, 'replicates' => 0, 'confirmation' => 0, 'confirmation_script' => '{}',
            'type' => 1, 'meta_assays' => '', 'max_count' => 300, 'min_count' => 10, 'script' => 'file:maz7218grens',
            'custom_fields' => '{}', 'duration' => '48', 'start_from' => '', 'article_code' => '',
        ]);
        $analysis = $sample->analyses()->create([
            'profile_group' => 1, 'follow_number' => 1, 'profile' => 0, 'assay' => $assay->id,
            'assay_base' => $assay->id, 'predicted_end' => -1, 'project' => $project->id,
        ]);
        $settings = $analysis->roamingSettings()->create([
            'assay' => $assay->id, 'dillutions' => [1], 'replicates' => 0, 'reference' => [],
        ]);
        $analysis->roamingAnalysis()->associate($settings);
        $analysis->save();
        $analysis->results()->create([
            'sample' => $sample->id, 'follow_no' => 1, 'profile' => 0, 'assay' => $assay->id,
            'assay_base' => $assay->id, 'roaming_id' => $settings->id, 'df' => '1', 'rep' => 0, 'data' => ['kve' => '+'],
        ]);

        return $analysis;
    }
}
