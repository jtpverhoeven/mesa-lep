<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
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
