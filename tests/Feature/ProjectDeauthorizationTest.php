<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\User;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ProjectDeauthorizationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:', 'services.glass.base_url' => 'http://portal.test/api']);
        DB::purge('sqlite');

        foreach (glob(database_path('migrations/*.php')) as $path) {
            (require $path)->up();
        }

        $this->actingAs(User::factory()->create(['enabled' => 1]));
        Gate::define('projects.authorisation.manage', fn (): bool => true);
        Http::preventStrayRequests();
        Http::fake(['http://portal.test/api/*' => Http::response(['ok' => true])]);
    }

    public function test_deauthorising_withdraws_the_authorisation_logs_the_reason_and_informs_the_portal(): void
    {
        $project = Project::query()->create([
            'client' => 1, 'subclient' => 0, 'auth_status' => 1, 'auth_by' => 1, 'auth_on' => 1700000000, 'revision' => 2, 'rap_stat' => 1,
        ]);

        $this->deleteJson(route('projects.authorization.destroy', $project), ['origin' => 'Intern (oorzaak/fout bij MAZ)', 'reason' => 'Foutieve invoer'])
            ->assertOk()->assertJsonPath('data.deauthorized', true)->assertJsonPath('data.portal_sync_failed', false);

        $this->assertDatabaseHas('projects', ['id' => $project->id, 'auth_status' => 0, 'auth_on' => 0, 'revision' => 3, 'rap_stat' => 0]);
        $this->assertDatabaseHas('changetracker', [
            'type' => '9', 'project' => $project->id, 'from' => '1', 'to' => '0',
            'event' => 'Project authorisatie ingetrokken, bron wijziging: Intern (oorzaak/fout bij MAZ), reden:Foutieve invoer',
        ]);
        Http::assertSentCount(1);
        Http::assertSent(fn (Request $request): bool => $request->url() === 'http://portal.test/api/projectDeauthorized'
            && collect($request->data())->firstWhere('name', 'preview')['contents'] === 'False');
    }

    public function test_deauthorising_a_project_that_is_not_authorised_changes_nothing(): void
    {
        $project = Project::query()->create(['client' => 1, 'subclient' => 0, 'auth_status' => 0, 'revision' => 2]);

        $this->deleteJson(route('projects.authorization.destroy', $project), ['origin' => 'Extern (oorzaak/fout bij klant)', 'reason' => 'Nieuwe wens'])
            ->assertOk()->assertJsonPath('data.deauthorized', false);

        $this->assertDatabaseHas('projects', ['id' => $project->id, 'auth_status' => 0, 'revision' => 2]);
        $this->assertDatabaseCount('changetracker', 0);
        Http::assertNothingSent();
    }
}
