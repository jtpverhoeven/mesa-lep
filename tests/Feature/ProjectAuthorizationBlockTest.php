<?php

namespace Tests\Feature;

use App\Events\ProjectEditabilityChanged;
use App\Models\Project;
use App\Models\Sample;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Tests\TestCase;

class ProjectAuthorizationBlockTest extends TestCase
{
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');
        foreach (glob(database_path('migrations/*.php')) as $path) {
            (require $path)->up();
        }
        Event::fake([ProjectEditabilityChanged::class]);
        $this->user = User::factory()->create(['enabled' => 1]);
        $this->actingAs($this->user);
        foreach (['projects.authorisation.block', 'samples.view', 'samples.update'] as $permission) {
            Gate::define($permission, fn () => true);
        }
    }

    public function test_blocking_makes_the_project_read_only_tracks_the_change_and_broadcasts(): void
    {
        $project = $this->project();

        $this->postJson(route('projects.authorization-block.store', $project), ['message' => 'Wacht op herbemonstering'])
            ->assertOk()
            ->assertJsonPath('data.project.locked', 1)
            ->assertJsonPath('data.project.locked_by', $this->user->id)
            ->assertJsonPath('data.project.lock_message', 'Wacht op herbemonstering')
            ->assertJsonPath('data.read_only', true)
            ->assertJsonPath('data.reasons', ['authorization_blocked']);

        $this->assertDatabaseHas('changetracker', [
            'type' => '22', 'project' => $project->id, 'user_id' => $this->user->id,
            'event' => 'Projectautorisatie geblokkeerd: Wacht op herbemonstering', 'from' => '0', 'to' => '1',
        ]);
        Event::assertDispatched(ProjectEditabilityChanged::class, fn (ProjectEditabilityChanged $event): bool => $event->projectId === $project->id
            && $event->readOnly && $event->reasons === ['authorization_blocked'] && $event->lockMessage === 'Wacht op herbemonstering'
            && $event->broadcastOn()[0]->name === 'presence-keyrings.project.'.$project->id);
    }

    public function test_writes_are_rejected_while_blocked_and_allowed_after_unblocking(): void
    {
        $sample = $this->sample($this->project());
        $this->postJson(route('projects.authorization-block.store', $sample->project))->assertOk();

        $this->updateDescription($sample)->assertStatus(423)->assertJsonPath('reason', 'authorization_blocked');

        $this->deleteJson(route('projects.authorization-block.destroy', $sample->project))
            ->assertOk()
            ->assertJsonPath('data.project.locked', 0)
            ->assertJsonPath('data.project.locked_by', null)
            ->assertJsonPath('data.project.lock_message', null)
            ->assertJsonPath('data.read_only', false);
        $this->assertDatabaseHas('changetracker', ['type' => '23', 'project' => $sample->project, 'from' => '1', 'to' => '0']);
        Event::assertDispatched(ProjectEditabilityChanged::class, fn (ProjectEditabilityChanged $event): bool => ! $event->readOnly && $event->reasons === []);

        $this->updateDescription($sample)->assertOk();
    }

    public function test_blocking_and_unblocking_are_rejected_in_the_wrong_project_state(): void
    {
        $authorized = $this->project(['auth_status' => 1]);
        $blocked = $this->project(['locked' => 1, 'locked_by' => $this->user->id]);
        $open = $this->project();

        $this->postJson(route('projects.authorization-block.store', $authorized))->assertUnprocessable()->assertJsonValidationErrors('project');
        $this->postJson(route('projects.authorization-block.store', $blocked))->assertUnprocessable()->assertJsonValidationErrors('project');
        $this->deleteJson(route('projects.authorization-block.destroy', $open))->assertUnprocessable()->assertJsonValidationErrors('project');
        $this->postJson(route('projects.authorization-block.store', $open), ['message' => Str::repeat('a', 1001)])
            ->assertUnprocessable()->assertJsonValidationErrors('message');

        $this->assertDatabaseCount('changetracker', 0);
        $this->assertDatabaseHas('projects', ['id' => $open->id, 'locked' => 0]);
        Event::assertNotDispatched(ProjectEditabilityChanged::class);
    }

    public function test_blocking_requires_the_block_permission(): void
    {
        Gate::define('projects.authorisation.block', fn () => false);
        $project = $this->project();

        $this->postJson(route('projects.authorization-block.store', $project))->assertForbidden();
        $this->deleteJson(route('projects.authorization-block.destroy', $project))->assertForbidden();

        $this->assertDatabaseHas('projects', ['id' => $project->id, 'locked' => 0]);
    }

    private function updateDescription(Sample $sample)
    {
        return $this->patchJson(route('samples.lookup.details.update', ['project' => $sample->project, 'sample' => $sample->id]), [
            'source' => 'attribute', 'field' => 'description', 'value' => 'Updated water sample',
        ]);
    }

    private function project(array $attributes = []): Project
    {
        return Project::create(['client' => 1, 'subclient' => 0, 'project_name' => 'Project water', 'lock_pass' => 'private', ...$attributes]);
    }

    private function sample(Project $project): Sample
    {
        return Sample::create([
            'barcode' => '26091000', 'follow_no' => 1000, 'description' => 'Water sample',
            'sampling_method' => 1, 'date_registered' => '1788998400', 'registered_by' => 1,
            'client' => 1, 'subclient' => 0, 'project' => $project->id,
            'custom_fields' => '{}', 'predicted_end' => 1789171200, 'sample_innoculated' => '', 'analyses_data' => '[]',
        ]);
    }
}
