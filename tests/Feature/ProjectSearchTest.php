<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\Sample;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ProjectSearchTest extends TestCase
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
        Gate::define('projects.view', fn () => true);
    }

    #[DataProvider('sampleDeletionCases')]
    public function test_sample_deletion_requires_permission_and_an_editable_owning_project(bool $permission, int $authorized, int $locked, bool $otherProject, int $status): void
    {
        Gate::define('projects.samples.remove', fn () => $permission);
        $project = Project::create(['client' => 1, 'subclient' => 0, 'project_name' => 'Deletion', 'auth_status' => $authorized, 'locked' => $locked]);
        $owner = $otherProject ? Project::create(['client' => 1, 'subclient' => 0, 'project_name' => 'Other']) : $project;
        $sample = Sample::create([
            'barcode' => '26101001', 'follow_no' => 1, 'description' => 'Delete me',
            'sampling_method' => 1, 'date_registered' => '1791590400', 'registered_by' => 1,
            'client' => 1, 'subclient' => 0, 'project' => $owner->id,
            'custom_fields' => '{}', 'predicted_end' => -1, 'sample_innoculated' => '',
            'analyses_data' => '[]', 'sample_type' => 'S', 'sample_extra' => '{}',
        ]);
        $metadata = $sample->metadata()->create(['name' => 'Location', 'value' => 'Lab', 'meta_order' => 1]);
        $analysis = $sample->analyses()->create([
            'profile_group' => 1, 'follow_number' => 1, 'profile' => 0,
            'assay' => 1, 'assay_base' => 1, 'predicted_end' => -1, 'project' => $owner->id,
        ]);
        $analysis->results()->create([
            'sample' => $sample->id, 'follow_no' => 1, 'profile' => 0,
            'assay' => 1, 'assay_base' => 1, 'roaming_id' => 0, 'df' => '1', 'rep' => 1, 'data' => '{}',
        ]);
        $analysis->roamingSettings()->create(['assay' => 1, 'dillutions' => '[]', 'reference' => '']);
        $analysis->confirmationRecord()->create(['note' => 'Confirmation']);

        $this->deleteJson(route('projects.search.samples.destroy', ['project' => $project, 'sample' => $sample]))->assertStatus($status);

        if ($status === 200) {
            $this->assertDatabaseMissing('samples', ['id' => $sample->id]);
            $this->assertDatabaseMissing('metadata', ['id' => $metadata->id]);
            $this->assertDatabaseMissing('sampleanalysis', ['id' => $analysis->id]);
            $this->assertDatabaseMissing('results', ['sa_id' => $analysis->id]);
            $this->assertDatabaseMissing('roaminganalysis', ['said' => $analysis->id]);
            $this->assertDatabaseMissing('confirmations', ['said' => $analysis->id]);
        } else {
            $this->assertDatabaseHas('samples', ['id' => $sample->id]);
            $this->assertDatabaseHas('metadata', ['id' => $metadata->id]);
            $this->assertDatabaseHas('sampleanalysis', ['id' => $analysis->id]);
            $this->assertDatabaseHas('results', ['sa_id' => $analysis->id]);
            $this->assertDatabaseHas('roaminganalysis', ['said' => $analysis->id]);
            $this->assertDatabaseHas('confirmations', ['said' => $analysis->id]);
        }
    }

    public static function sampleDeletionCases(): array
    {
        return [
            'editable project' => [true, 0, 0, false, 200],
            'missing permission' => [false, 0, 0, false, 403],
            'authorized project' => [true, 1, 0, false, 403],
            'locked project' => [true, 0, 1, false, 403],
            'different project' => [true, 0, 0, true, 404],
        ];
    }

    public function test_project_page_passes_the_route_project_to_vue(): void
    {
        $project = Project::forceCreate(['id' => 11, 'client' => 1, 'subclient' => 0, 'project_name' => 'Project water']);

        $this->get('/laboratory/projects/search/11')
            ->assertOk()
            ->assertViewIs('projects.search')
            ->assertViewHas('initialProjectId', $project->id)
            ->assertSee('initialProjectId');
        $this->get(route('projects.search'))->assertOk()->assertViewHas('initialProjectId', null);
    }

    public function test_project_details_load_from_the_separate_json_route(): void
    {
        $project = Project::create(['client' => 1, 'subclient' => 0, 'project_name' => 'Project water']);

        $this->getJson('/laboratory/projects/search/data/'.$project->id)
            ->assertOk()
            ->assertJsonPath('data.project.id', $project->id)
            ->assertJsonPath('data.project.project_name', 'Project water')
            ->assertJsonPath('data.url', url('/laboratory/projects/search/'.$project->id));
        $this->getJson(route('projects.search.data', ['mode' => 'reference', 'query' => 'Project water']))
            ->assertOk();
    }

    #[DataProvider('sampleExtraFields')]
    public function test_project_lookup_only_shows_extra_fields_for_the_selected_sample_type(string $sampleType, array $expected): void
    {
        $project = Project::create(['client' => 1, 'subclient' => 0, 'project_name' => 'Project water']);
        $sample = Sample::create([
            'barcode' => '26091000', 'follow_no' => 1000, 'description' => 'Sample',
            'sampling_method' => 1, 'date_registered' => '1788998400', 'registered_by' => 1,
            'client' => 1, 'subclient' => 0, 'project' => $project->id,
            'custom_fields' => '{}', 'predicted_end' => -1,
            'sample_innoculated' => '', 'analyses_data' => '[]', 'sample_type' => $sampleType,
            'sample_extra' => json_encode([
                'follow' => '7', 'type' => 'Warm', 'temperature' => '42',
                'location' => 'Cleanroom', 'filter_volume' => '1000', 'unrelated' => 'hidden',
            ]),
        ]);

        $this->getJson(route('projects.search.samples.show', ['project' => $project, 'sample' => $sample]))
            ->assertOk()
            ->assertJsonPath('data.sample_extra', $expected)
            ->assertJsonPath('data.project_follow_number', 1);
    }

    public static function sampleExtraFields(): array
    {
        return [
            'normal has no extras' => ['S', []],
            'legionella has water fields only' => ['L', [
                ['name' => 'type', 'label' => 'Leiding', 'type' => 'select', 'value' => 'Warm'],
                ['name' => 'temperature', 'label' => 'Temperatuur', 'type' => 'text', 'value' => '42'],
                ['name' => 'filter_volume', 'label' => 'Onderzocht volume in ml.', 'type' => 'number', 'value' => '1000'],
            ]],
            'rodac has room only' => ['R', [
                ['name' => 'location', 'label' => 'Ruimte', 'type' => 'text', 'value' => 'Cleanroom'],
            ]],
        ];
    }

    public function test_project_page_returns_404_for_missing_or_invalid_projects(): void
    {
        $this->get('/laboratory/projects/search/999')->assertNotFound();
        $this->get('/laboratory/projects/search/invalid')->assertNotFound();
    }

    public function test_project_page_and_details_return_403_without_view_permission(): void
    {
        $project = Project::create(['client' => 1, 'subclient' => 0, 'project_name' => 'Project water']);
        Gate::define('projects.view', fn () => false);

        $this->get(route('projects.search', ['project' => $project]))->assertForbidden();
        $this->getJson(route('projects.search.show', ['project' => $project]))->assertForbidden();
    }

    public function test_project_page_redirects_guests_to_login(): void
    {
        auth()->logout();

        $this->get('/laboratory/projects/search/11')->assertRedirect(route('login'));
    }
}
