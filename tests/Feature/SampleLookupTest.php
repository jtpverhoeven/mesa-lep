<?php

namespace Tests\Feature;

use App\Models\Assay;
use App\Models\ChangeRevision;
use App\Models\Project;
use App\Models\Sample;
use App\Models\SampleField;
use App\Models\SampleProcedure;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
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
    public function test_sample_detail_changes_return_403_without_permission_or_for_frozen_projects(bool $canView, bool $canUpdate, int $authorized, int $locked): void
    {
        Gate::define('samples.view', fn () => $canView);
        Gate::define('samples.update', fn () => $canUpdate);
        $sample = $this->sample('26091000');
        Project::find($sample->project)->update(['auth_status' => $authorized, 'locked' => $locked]);

        $this->patchJson(route('samples.lookup.details.update', ['project' => $sample->project, 'sample' => $sample->id]), [
            'source' => 'attribute', 'field' => 'description', 'value' => 'Not allowed',
        ])->assertForbidden();

        $this->assertDatabaseHas('samples', ['id' => $sample->id, 'description' => 'Water sample']);
        $this->assertDatabaseCount(ChangeRevision::class, 0);
    }

    public static function deniedDetailChanges(): array
    {
        return [
            'no sample view permission' => [false, true, 0, 0],
            'no sample update permission' => [true, false, 0, 0],
            'authorized project' => [true, true, 1, 0],
            'locked project' => [true, true, 0, 1],
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
        $this->postJson($url, $payload)->assertUnprocessable();
    }

    public function test_addition_rolls_back_when_a_later_entry_is_unavailable(): void
    {
        $sample = $this->sample('26091000');
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

    private function assay(): Assay
    {
        return Assay::create([
            'original_id' => 1, 'name' => 'Total count', 'type_base' => 1, 'media_id' => '[]',
            'dillution' => 0, 'replicates' => 1, 'confirmation' => 0, 'confirmation_script' => '{}',
            'type' => 1, 'meta_assays' => '', 'max_count' => 300, 'min_count' => 10,
            'script' => '', 'custom_fields' => '{}', 'duration' => '48', 'start_from' => '', 'article_code' => '',
        ]);
    }
}
