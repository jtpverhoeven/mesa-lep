<?php

namespace Tests\Feature;

use App\Models\Assay;
use App\Models\ChangeRevision;
use App\Models\Project;
use App\Models\Sample;
use App\Models\SampleField;
use App\Models\SampleProcedure;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class SampleLookupTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');
        foreach (glob(database_path('migrations/*.php')) as $path) {
            (require $path)->up();
        }
        $this->actingAs(User::factory()->create(['enabled' => 1]));
        foreach (['samples.view', 'samples.assign-research', 'samples.update-research'] as $permission) {
            Gate::define($permission, fn () => true);
        }
    }

    public function test_lookup_page_passes_the_route_barcode_to_vue(): void
    {
        $this->get('/laboratory/samples/lookup/26091016')
            ->assertOk()
            ->assertViewIs('samples.lookup')
            ->assertViewHas('initialBarcode', '26091016')
            ->assertSee('initialBarcode');
        $this->get(route('samples.lookup', ['barcode' => '26091016.2.3']))
            ->assertOk()
            ->assertViewHas('initialBarcode', '26091016.2.3');
        $this->get(route('samples.lookup'))->assertOk()->assertViewHas('initialBarcode', '');
    }

    public function test_lookup_page_returns_403_without_view_permission(): void
    {
        Gate::define('samples.view', fn () => false);

        $this->get('/laboratory/samples/lookup/26091016')->assertForbidden();
    }

    public function test_lookup_returns_legacy_data_and_project_navigation_without_sensitive_fields(): void
    {
        $sample = $this->sample('26091000');
        $next = $this->sample('26091001', $sample->project);
        $this->get(route('samples.lookup'))->assertOk()->assertSee('sample-lookup');
        $this->getJson(route('samples.lookup.data', ['barcode' => ' 26091000 ']))
            ->assertOk()
            ->assertJsonPath('data.sample.id', $sample->id)
            ->assertJsonPath('data.project_follow_number', 1)
            ->assertJsonPath('data.sample_fields.0.value', '4 C')
            ->assertJsonPath('data.project.project_name', 'Project water')
            ->assertJsonPath('data.project_url', url('/laboratory/projects/search/'.$sample->project))
            ->assertJsonPath('data.project_samples.1.barcode', $next->barcode)
            ->assertJsonPath('data.previous', null)
            ->assertJsonPath('data.next', $next->barcode)
            ->assertJsonMissingPath('data.project.lock_pass');
        $this->getJson(route('samples.lookup.data', ['barcode' => $next->barcode]))
            ->assertOk()->assertJsonPath('data.project_follow_number', 2);
        $this->getJson(route('samples.lookup.data', ['barcode' => 'unknown']))->assertNotFound();
        $this->getJson(route('samples.lookup.data'))->assertUnprocessable();
    }

    #[DataProvider('predictedProgressCases')]
    public function test_lookup_progress_measures_elapsed_time_not_completed_results(int $start, int $end, int $authorized, int $expected): void
    {
        $this->travelTo(CarbonImmutable::createFromTimestampUTC(1789084800));
        $sample = $this->sample('26091000');
        $sample->update(['date_registered' => (string) $start, 'predicted_end' => $end]);
        Project::find($sample->project)->update(['auth_status' => $authorized]);

        $this->getJson(route('samples.lookup.data', ['barcode' => $sample->barcode]))
            ->assertOk()
            ->assertJsonPath('data.progress', $expected);
    }

    public static function predictedProgressCases(): array
    {
        return [
            'half elapsed without results' => [1788998400, 1789171200, 0, 50],
            'rounds elapsed percentage up' => [1788998400, 1789257600, 0, 34],
            'overdue is capped' => [1788825600, 1788998400, 0, 100],
            'not started is zero' => [1789171200, 1789257600, 0, 0],
            'unknown inoculation date' => [1788998400, -1, 0, 0],
            'unset predicted date' => [1788998400, 0, 0, 0],
            'authorized overrides unknown date' => [1788998400, -1, 1, 100],
            'zero interval follows legacy lookup' => [1788998400, 1788998400, 0, 0],
        ];
    }

    #[DataProvider('sampleExtraFields')]
    public function test_lookup_only_shows_extra_fields_for_its_sample_type(string $sampleType, array $expected): void
    {
        $sample = $this->sample('26091000');
        $sample->update([
            'sample_type' => $sampleType,
            'sample_extra' => json_encode([
                'follow' => '7', 'type' => 'Warm', 'temperature' => '42',
                'location' => 'Cleanroom', 'filter_volume' => '1000', 'unrelated' => 'hidden',
            ]),
        ]);

        $this->getJson(route('samples.lookup.data', ['barcode' => $sample->barcode]))
            ->assertOk()
            ->assertJsonPath('data.sample_extra', $expected)
            ->assertJsonPath('data.project_follow_number', 1);
    }

    public static function sampleExtraFields(): array
    {
        return [
            'normal has no extras' => ['S', []],
            'legionella has water fields only' => ['L', [
                ['name' => 'type', 'label' => 'Tappunt type', 'value' => 'Warm'],
                ['name' => 'temperature', 'label' => 'Temperatuur', 'value' => '42'],
                ['name' => 'filter_volume', 'label' => 'Onderzocht volume in ml.', 'value' => '1000'],
            ]],
            'rodac has room only' => ['R', [
                ['name' => 'location', 'label' => 'Ruimte', 'value' => 'Cleanroom'],
            ]],
        ];
    }

    public function test_lookup_and_mutations_require_their_own_permissions(): void
    {
        $sample = $this->sample('26091000');
        Gate::define('samples.view', fn () => false);
        $this->getJson(route('samples.lookup.data', ['barcode' => $sample->barcode]))->assertForbidden();
        Gate::define('samples.view', fn () => true);
        Gate::define('samples.assign-research', fn () => false);
        $this->postJson(route('samples.lookup.research', $sample), ['operation' => 'add'])->assertForbidden();
        Gate::define('samples.update-research', fn () => false);
        $this->postJson(route('samples.lookup.research', $sample), ['operation' => 'remove', 'analysis_id' => 1])->assertForbidden();
    }

    public function test_sample_details_can_be_read_and_changed_without_project_view_permission(): void
    {
        Gate::define('projects.view', fn () => false);
        Gate::define('samples.update', fn () => true);
        $sample = $this->sample('26091000');
        $parameters = ['project' => $sample->project, 'sample' => $sample->id];

        $this->getJson(route('samples.lookup.details', $parameters))
            ->assertOk()
            ->assertJsonPath('data.sample.description', 'Water sample')
            ->assertJsonPath('data.read_only', false)
            ->assertJsonMissingPath('data.project.lock_pass');
        $this->patchJson(route('samples.lookup.details.update', $parameters), [
            'source' => 'attribute', 'field' => 'description', 'value' => 'Updated water sample',
        ])->assertOk()->assertJsonPath('data.sample.description', 'Updated water sample');

        $this->assertDatabaseHas('samples', ['id' => $sample->id, 'description' => 'Updated water sample']);
        $this->assertDatabaseHas(ChangeRevision::class, [
            'sample' => $sample->id, 'project' => $sample->project,
            'from' => 'Water sample', 'to' => 'Updated water sample',
        ]);
    }

    #[DataProvider('deniedDetailChanges')]
    public function test_sample_detail_changes_are_rejected_without_permission_or_for_frozen_projects(bool $canView, bool $canUpdate, int $authorized, int $locked, int $status): void
    {
        Gate::define('samples.view', fn () => $canView);
        Gate::define('samples.update', fn () => $canUpdate);
        $sample = $this->sample('26091000');
        Project::find($sample->project)->update(['auth_status' => $authorized, 'locked' => $locked]);

        $this->patchJson(route('samples.lookup.details.update', ['project' => $sample->project, 'sample' => $sample->id]), [
            'source' => 'attribute', 'field' => 'description', 'value' => 'Not allowed',
        ])->assertStatus($status);

        $this->assertDatabaseHas('samples', ['id' => $sample->id, 'description' => 'Water sample']);
        $this->assertDatabaseCount(ChangeRevision::class, 0);
    }

    public static function deniedDetailChanges(): array
    {
        return [
            'no sample view permission' => [false, true, 0, 0, 403],
            'no sample update permission' => [true, false, 0, 0, 403],
            'authorized project' => [true, true, 1, 0, 423],
            'locked project' => [true, true, 0, 1, 423],
        ];
    }

    #[DataProvider('legacyDetailFields')]
    public function test_sample_details_update_legacy_json_fields_without_losing_other_values(string $source, string $field, string $value, string $column): void
    {
        Gate::define('samples.update', fn () => true);
        $sample = $this->sample('26091000');
        SampleField::create(['name' => 'temperature', 'alias' => 'Temperatuur', 'type' => 'text', 'std_value' => '', 'position' => 1]);
        $sample->update(['sample_type' => 'L', 'sample_extra' => '{"follow":"7","type":"Warm","filter_volume":"1000"}']);

        $this->patchJson(route('samples.lookup.details.update', ['project' => $sample->project, 'sample' => $sample->id]), [
            'source' => $source, 'field' => $field, 'value' => $value,
        ])->assertOk();

        $this->assertSame($value, json_decode($sample->fresh()->{$column}, true)[$field]);
        $this->assertSame('7', json_decode($sample->fresh()->sample_extra, true)['follow']);
        $this->assertDatabaseHas(ChangeRevision::class, ['sample' => $sample->id, 'to' => $value]);
    }

    public static function legacyDetailFields(): array
    {
        return [
            'custom field' => ['custom', 'temperature', '8 C', 'custom_fields'],
            'legionella volume' => ['extra', 'filter_volume', '500,5', 'sample_extra'],
        ];
    }

    public function test_sample_details_accept_active_sampling_methods_and_reject_inactive_methods(): void
    {
        Gate::define('samples.update', fn () => true);
        $sample = $this->sample('26091000');
        SampleProcedure::create(['name' => 'Original method', 'active' => 1, 'fields' => '[]']);
        $active = SampleProcedure::create(['name' => 'Updated method', 'active' => 1, 'fields' => '[]']);
        $inactive = SampleProcedure::create(['name' => 'Inactive method', 'active' => 0, 'fields' => '[]']);
        $url = route('samples.lookup.details.update', ['project' => $sample->project, 'sample' => $sample->id]);

        $this->patchJson($url, ['source' => 'attribute', 'field' => 'sampling_method', 'value' => (string) $active->id])
            ->assertOk()->assertJsonPath('data.sample.sampling_method', $active->id);
        $this->patchJson($url, ['source' => 'attribute', 'field' => 'sampling_method', 'value' => (string) $inactive->id])
            ->assertInvalid(['value' => 'Selecteer een geldige monsternameprocedure.']);

        $this->assertDatabaseHas('samples', ['id' => $sample->id, 'sampling_method' => $active->id]);
        $this->assertDatabaseCount(ChangeRevision::class, 1);
    }

    public function test_sample_details_return_404_for_another_project_and_reject_noneditable_fields(): void
    {
        Gate::define('samples.update', fn () => true);
        $sample = $this->sample('26091000');
        $other = $this->sample('26091001');
        $wrongProject = ['project' => $other->project, 'sample' => $sample->id];

        $this->getJson(route('samples.lookup.details', $wrongProject))->assertNotFound();
        $this->patchJson(route('samples.lookup.details.update', $wrongProject), [
            'source' => 'attribute', 'field' => 'description', 'value' => 'Not allowed',
        ])->assertNotFound();
        $this->patchJson(route('samples.lookup.details.update', ['project' => $sample->project, 'sample' => $sample->id]), [
            'source' => 'attribute', 'field' => 'client_description', 'value' => 'Not allowed',
        ])->assertInvalid(['field' => 'Dit monsterveld kan niet worden gewijzigd.']);

        $this->assertDatabaseHas('samples', ['id' => $sample->id, 'description' => 'Water sample']);
        $this->assertDatabaseCount(ChangeRevision::class, 0);
    }

    public function test_research_can_be_added_reordered_and_removed_only_from_its_sample(): void
    {
        $sample = $this->sample('26091000');
        $assay = $this->assay();
        $url = route('samples.lookup.research', $sample);
        $payload = ['operation' => 'add', 'analyses' => [['type' => 'assay', 'assay_id' => $assay->id, 'settings' => ['replicates' => 2]]]];
        $this->postJson($url, $payload)->assertOk()->assertJsonPath('data.analyses.0.name', 'Total count');
        $this->postJson($url, $payload)->assertOk();
        $ids = $sample->analyses()->pluck('id')->all();
        $this->postJson($url, ['operation' => 'reorder', 'analysis_ids' => [$ids[0]]])->assertUnprocessable();
        $this->postJson($url, ['operation' => 'reorder', 'analysis_ids' => array_reverse($ids)])
            ->assertOk()->assertJsonPath('data.analyses.0.id', $ids[1]);
        $other = $this->sample('26091001');
        $this->postJson(route('samples.lookup.research', $other), ['operation' => 'remove', 'analysis_id' => $ids[0]])->assertNotFound();
        $this->postJson($url, ['operation' => 'remove', 'analysis_id' => $ids[0]])->assertOk()->assertJsonCount(1, 'data.analyses');
        $this->assertDatabaseMissing('roaminganalysis', ['said' => $ids[0]]);
        $sample->analyses()->first()->update(['is_ready' => true]);
        $this->postJson($url, ['operation' => 'remove', 'analysis_id' => $ids[1]])->assertUnprocessable();
        Project::find($sample->project)->update(['locked' => 1]);
        $this->postJson($url, $payload)->assertStatus(423)->assertJsonPath('reason', 'authorization_blocked');
    }

    public function test_addition_rolls_back_when_a_later_entry_is_unavailable(): void
    {
        $sample = $this->sample('26091000');
        $previousProjectEnd = Project::find($sample->project)->predicted_end;
        $assay = $this->assay();
        $inactive = $this->assay();
        $inactive->update(['active' => 0]);
        $this->postJson(route('samples.lookup.research', $sample), ['operation' => 'add', 'analyses' => [
            ['type' => 'assay', 'assay_id' => $assay->id, 'settings' => ['replicates' => 1]],
            ['type' => 'assay', 'assay_id' => $inactive->id, 'settings' => ['replicates' => 1]],
        ]])->assertUnprocessable();
        $this->assertDatabaseCount('sampleanalysis', 0);
        $this->assertDatabaseCount('roaminganalysis', 0);
        $this->assertSame(1, $sample->fresh()->isEmpty);
        $this->assertSame(1789171200, $sample->fresh()->predicted_end);
        $this->assertSame($previousProjectEnd, Project::find($sample->project)->predicted_end);
    }

    public function test_adding_and_removing_research_recalculates_the_longest_endpoint_and_elapsed_progress(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-10-10 00:00:00', 'UTC'));
        $sample = $this->sample('26091000');
        $sample->update(['date_registered' => (string) CarbonImmutable::parse('2026-10-09', 'UTC')->timestamp]);
        $sibling = $this->sample('26091001', $sample->project);
        $siblingEnd = CarbonImmutable::parse('2026-10-15', 'UTC')->timestamp;
        $sibling->update(['predicted_end' => $siblingEnd]);
        $short = $this->assay(['duration' => '1', 'start_from' => 'r']);
        $long = $this->assay(['duration' => '4', 'start_from' => 'r']);
        $url = route('samples.lookup.research', $sample);
        $monday = CarbonImmutable::parse('2026-10-12', 'UTC')->timestamp;
        $tuesday = CarbonImmutable::parse('2026-10-13', 'UTC')->timestamp;

        $this->postJson($url, ['operation' => 'add', 'analyses' => [['type' => 'assay', 'assay_id' => $short->id, 'settings' => ['replicates' => 1]]]])
            ->assertOk()->assertJsonPath('data.sample.predicted_end', $monday)->assertJsonPath('data.progress', 34)
            ->assertJsonPath('data.analyses.0.predicted_end', $monday);
        $shortId = $sample->analyses()->value('id');
        $this->postJson($url, ['operation' => 'add', 'analyses' => [['type' => 'assay', 'assay_id' => $long->id, 'settings' => ['replicates' => 1]]]])
            ->assertOk()->assertJsonPath('data.sample.predicted_end', $tuesday)->assertJsonPath('data.progress', 25)
            ->assertJsonPath('data.analyses.0.predicted_end', $monday)->assertJsonPath('data.analyses.1.predicted_end', $tuesday);
        $longId = $sample->analyses()->where('assay_base', $long->id)->value('id');
        $this->assertDatabaseHas('projects', ['id' => $sample->project, 'predicted_end' => $siblingEnd]);

        $this->postJson($url, ['operation' => 'remove', 'analysis_id' => $longId])
            ->assertOk()->assertJsonPath('data.sample.predicted_end', $monday)->assertJsonPath('data.progress', 34);
        $this->assertDatabaseHas('projects', ['id' => $sample->project, 'predicted_end' => $siblingEnd]);
        $this->postJson($url, ['operation' => 'remove', 'analysis_id' => $shortId])
            ->assertOk()->assertJsonPath('data.sample.predicted_end', now()->timestamp);
        $this->assertSame(1, $sample->fresh()->isEmpty);
    }

    public function test_one_unknown_analysis_keeps_sample_and_project_unknown_until_it_is_removed(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-10-09 12:00:00', 'UTC'));
        $sample = $this->sample('26091000');
        $sample->update(['date_registered' => (string) now()->timestamp]);
        $known = $this->assay(['duration' => '1', 'start_from' => 'r']);
        $unknown = $this->assay(['duration' => '2', 'start_from' => 'i']);
        $monday = CarbonImmutable::parse('2026-10-12', 'UTC')->timestamp;
        $url = route('samples.lookup.research', $sample);

        $this->postJson($url, ['operation' => 'add', 'analyses' => [
            ['type' => 'assay', 'assay_id' => $known->id, 'settings' => ['replicates' => 1]],
            ['type' => 'assay', 'assay_id' => $unknown->id, 'settings' => ['replicates' => 1]],
        ]])->assertOk()->assertJsonPath('data.sample.predicted_end', -1)->assertJsonPath('data.progress', 0)
            ->assertJsonPath('data.analyses.0.predicted_end', $monday)->assertJsonPath('data.analyses.1.predicted_end', -1);
        $this->assertDatabaseHas('projects', ['id' => $sample->project, 'predicted_end' => -1]);

        $this->postJson($url, ['operation' => 'remove', 'analysis_id' => $sample->analyses()->where('assay_base', $unknown->id)->value('id')])
            ->assertOk()->assertJsonPath('data.sample.predicted_end', $monday);
        $this->assertDatabaseHas('projects', ['id' => $sample->project, 'predicted_end' => $monday]);
    }

    #[DataProvider('analysisAnchors')]
    public function test_endpoint_recalculation_uses_legacy_anchors_and_durations(string $startFrom, string $duration, string $inoculated, array $sampleFields, array $projectFields, int|string $expected): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-10-10 12:00:00', 'UTC'));
        $sample = $this->sample('26091000');
        $sample->update([
            'date_registered' => (string) CarbonImmutable::parse('2026-10-08 12:00:00', 'UTC')->timestamp,
            'sample_innoculated' => $inoculated,
            'custom_fields' => json_encode($sampleFields),
        ]);
        Project::find($sample->project)->update(['custom_fields' => json_encode($projectFields)]);
        $assay = $this->assay(['duration' => $duration, 'start_from' => $startFrom]);
        $analysisEnd = is_int($expected) ? $expected : CarbonImmutable::parse($expected, 'UTC')->timestamp;
        $sampleEnd = $analysisEnd === 0 ? now()->timestamp : $analysisEnd;
        $sample->load(['analyses', 'project']);

        $this->postJson(route('samples.lookup.research', $sample), ['operation' => 'add', 'analyses' => [
            ['type' => 'assay', 'assay_id' => $assay->id, 'settings' => ['replicates' => 1]],
        ]])->assertOk()->assertJsonPath('data.sample.predicted_end', $sampleEnd)
            ->assertJsonPath('data.analyses.0.predicted_end', $analysisEnd);

        $this->assertSame($sampleEnd, $sample->recalculatePredictedEnd());
        $this->assertSame($sampleEnd, $sample->predicted_end);
        $this->assertSame($analysisEnd, $sample->analyses->first()->predicted_end);
        $this->assertSame($sampleEnd, (int) $sample->getRelation('project')->predicted_end);
        $this->assertDatabaseHas('samples', ['id' => $sample->id, 'predicted_end' => $sampleEnd]);
        $this->assertDatabaseHas('projects', ['id' => $sample->project, 'predicted_end' => $sampleEnd]);
    }

    public static function analysisAnchors(): array
    {
        return [
            'registration plus days' => ['r', '1', '', [], [], '2026-10-09'],
            'fractional day shifts to monday' => ['r', '1.5', '', [], [], '2026-10-12'],
            'empty duration is zero' => ['r', '', '', [], [], '2026-10-08'],
            'inoculation plus days' => ['i', '3', '1791547200', [], [], '2026-10-12'],
            'inoculation not started' => ['i', '3', '', [], [], -1],
            'zero inoculation sentinel' => ['i', '3', '0', [], [], -1],
            'dash inoculation sentinel' => ['i', '3', '-', [], [], -1],
            'project field' => ['p:received', '1', '', [], ['received' => '09-10-2026'], '2026-10-12'],
            'sample field' => ['s:collected', '2', '', ['collected' => '11-10-2026'], [], '2026-10-13'],
            'missing custom anchor retains zero fallback' => ['s:missing', '0', '', [], [], 0],
        ];
    }

    #[DataProvider('inoculationSampleTypes')]
    public function test_inoculation_and_date_corrections_recalculate_endpoints_for_every_sample_type(string $sampleType): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-10-09 12:00:00', 'UTC'));
        Queue::fake();
        Gate::define('samples.list', fn () => true);
        $sample = $this->sample('26091000');
        $sample->update(['sample_type' => $sampleType]);
        $assay = $this->assay(['duration' => '1', 'start_from' => 'i']);
        $monday = CarbonImmutable::parse('2026-10-12', 'UTC')->timestamp;
        $tuesday = CarbonImmutable::parse('2026-10-13', 'UTC')->timestamp;
        $this->postJson(route('samples.lookup.research', $sample), ['operation' => 'add', 'analyses' => [
            ['type' => 'assay', 'assay_id' => $assay->id, 'settings' => ['replicates' => 1]],
        ]])->assertOk()->assertJsonPath('data.sample.predicted_end', -1);

        $this->postJson(route('samples.register.inoculation'), ['barcode' => $sample->barcode])->assertOk();

        $this->assertDatabaseHas('samples', ['id' => $sample->id, 'predicted_end' => $monday]);
        $this->assertDatabaseHas('projects', ['id' => $sample->project, 'predicted_end' => $monday]);
        $this->assertDatabaseHas('sampleanalysis', ['sample' => $sample->id, 'predicted_end' => $monday]);

        $this->withSession(['_token' => 'conditions-test-token'])->withHeader('X-CSRF-TOKEN', 'conditions-test-token')
            ->patchJson(route('samples.register.conditions', $sample), ['innoc' => '12-10-2026 12:00', 'storage' => 'B', 'diluted_at' => 'DIL'])
            ->assertOk();

        $this->assertDatabaseHas('samples', ['id' => $sample->id, 'predicted_end' => $tuesday]);
        $this->assertDatabaseHas('projects', ['id' => $sample->project, 'predicted_end' => $tuesday]);
        $this->assertDatabaseHas('sampleanalysis', ['sample' => $sample->id, 'predicted_end' => $tuesday]);
    }

    public static function inoculationSampleTypes(): array
    {
        return ['standard' => ['S'], 'legionella' => ['L'], 'rodac' => ['R']];
    }

    public function test_database_rejects_duplicate_barcodes(): void
    {
        $this->sample('26091000');
        $this->expectException(UniqueConstraintViolationException::class);
        $this->sample('26091000');
    }

    private function sample(string $barcode, ?int $projectId = null): Sample
    {
        $projectId ??= Project::create(['client' => 1, 'subclient' => 0, 'project_name' => 'Project water', 'lock_pass' => 'private'])->id;

        return Sample::create([
            'barcode' => $barcode, 'follow_no' => 1000, 'description' => 'Water sample',
            'sampling_method' => 1, 'date_registered' => '1788998400', 'registered_by' => 1,
            'client' => 1, 'subclient' => 0, 'project' => $projectId,
            'custom_fields' => '{"temperature":"4 C"}', 'predicted_end' => 1789171200,
            'sample_innoculated' => '', 'analyses_data' => '[]',
        ]);
    }

    private function assay(array $attributes = []): Assay
    {
        return Assay::create([
            'original_id' => 1, 'name' => 'Total count', 'type_base' => 1, 'media_id' => '[]',
            'dillution' => 0, 'replicates' => 1, 'confirmation' => 0, 'confirmation_script' => '{}',
            'type' => 1, 'meta_assays' => '', 'max_count' => 300, 'min_count' => 10,
            'script' => '', 'custom_fields' => '{}', 'duration' => '48', 'start_from' => '', 'article_code' => '',
            ...$attributes,
        ]);
    }
}
