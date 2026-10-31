<?php

namespace Tests\Feature;

use App\Actions\Keyrings\StealKeyring;
use App\Actions\Projects\EnsureEditable;
use App\Keyrings\KeyringPresence;
use App\Keyrings\KeyringType;
use App\Models\ChangeRevision;
use App\Models\Keyring;
use App\Models\Project;
use App\Models\Sample;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ProjectEditabilityTest extends TestCase
{
    private User $user;

    private User $holder;

    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');
        foreach (glob(database_path('migrations/*.php')) as $path) {
            (require $path)->up();
        }
        config(['keyrings.lifetime_minutes' => 30]);
        $this->travelTo(now()->startOfMinute());
        Event::fake();
        $this->user = User::factory()->create(['enabled' => 1, 'name' => 'Ada Analist']);
        $this->holder = User::factory()->create(['enabled' => 1, 'name' => 'Bob Bezet']);
        $this->actingAs($this->user);
        foreach (['samples.view', 'samples.update', 'projects.view', 'projects.samples.remove'] as $permission) {
            Gate::define($permission, fn () => true);
        }
    }

    public function test_a_write_is_rejected_with_423_while_another_present_user_holds_the_sample_keyring(): void
    {
        $sample = $this->sample();
        $keyring = Keyring::factory()->forResource(KeyringType::Sample, $sample->id)->heldBy($this->holder)->create();
        $this->presence(true);

        $this->updateDescription($sample)
            ->assertStatus(423)
            ->assertJsonPath('message', 'Dit monster is in gebruik door Bob Bezet.')
            ->assertJsonPath('reason', 'keyring')
            ->assertJsonPath('keyring.locked', true)
            ->assertJsonPath('keyring.type', 'sample')
            ->assertJsonPath('keyring.type_id', $sample->id)
            ->assertJsonPath('keyring.lock_id', $keyring->id)
            ->assertJsonPath('keyring.locked_by_id', $this->holder->id)
            ->assertJsonPath('keyring.locked_by_name', 'Bob Bezet');

        $this->assertDatabaseHas('samples', ['id' => $sample->id, 'description' => 'Water sample']);
        $this->assertDatabaseCount(ChangeRevision::class, 0);
        $this->assertSame($this->holder->id, $keyring->fresh()->issued_to);
    }

    public function test_writes_claim_a_free_keyring_and_refresh_an_own_keyring(): void
    {
        $sample = $this->sample();
        $this->presence(null);

        $this->updateDescription($sample, 'First change')->assertOk();
        $keyring = Keyring::query()->forResource(KeyringType::Sample, $sample->id)->sole();
        $this->assertSame($this->user->id, $keyring->issued_to);

        $this->travel(10)->minutes();
        $this->updateDescription($sample, 'Second change')->assertOk();

        $this->assertSame(now()->timestamp + 1800, $keyring->fresh()->expires);
        $this->assertDatabaseHas('samples', ['id' => $sample->id, 'description' => 'Second change']);
    }

    public function test_a_write_takes_over_the_keyring_of_a_holder_who_left(): void
    {
        $sample = $this->sample();
        $keyring = Keyring::factory()->forResource(KeyringType::Sample, $sample->id)->heldBy($this->holder)->create();
        $this->presence(false);

        $this->updateDescription($sample)->assertOk();

        $this->assertSame($this->user->id, $keyring->fresh()->issued_to);
        $this->assertDatabaseHas('samples', ['id' => $sample->id, 'description' => 'Updated water sample']);
    }

    public function test_the_previous_holder_cannot_write_after_the_keyring_was_stolen(): void
    {
        $sample = $this->sample();
        Keyring::factory()->forResource(KeyringType::Sample, $sample->id)->heldBy($this->user)->create();
        $this->presence(true);
        app(StealKeyring::class)->handle(KeyringType::Sample, $sample->id, $this->holder);

        $this->updateDescription($sample)->assertStatus(423)->assertJsonPath('keyring.locked_by_name', 'Bob Bezet');

        $this->assertDatabaseHas('samples', ['id' => $sample->id, 'description' => 'Water sample']);
    }

    #[DataProvider('frozenProjects')]
    public function test_the_project_rule_rejects_writes_before_claiming_a_keyring(array $state, string $reason): void
    {
        $sample = $this->sample($state);
        $this->presence(null);

        $this->updateDescription($sample)
            ->assertStatus(423)
            ->assertExactJson(['message' => 'Dit project is vergrendeld of geautoriseerd.', 'reason' => $reason, 'keyring' => null]);

        $this->assertDatabaseCount('keyrings', 0);
        $this->assertDatabaseHas('samples', ['id' => $sample->id, 'description' => 'Water sample']);
    }

    public static function frozenProjects(): array
    {
        return [
            'authorized' => [['auth_status' => 1], 'authorized'],
            'authorization blocked' => [['locked' => 1], 'authorization_blocked'],
        ];
    }

    public function test_project_level_writes_use_the_project_keyring(): void
    {
        $sample = $this->sample();
        Keyring::factory()->forResource(KeyringType::Project, $sample->project)->heldBy($this->holder)->create();
        $this->presence(true);

        $this->deleteJson(route('projects.search.samples.destroy', ['project' => $sample->project, 'sample' => $sample]))
            ->assertStatus(423)->assertJsonPath('message', 'Dit project is in gebruik door Bob Bezet.');

        $this->assertModelExists($sample);
    }

    public function test_writes_without_an_authenticated_user_skip_the_keyring(): void
    {
        $sample = $this->sample();
        Keyring::factory()->forResource(KeyringType::Sample, $sample->id)->heldBy($this->holder)->create();
        Auth::logout();

        app(EnsureEditable::class)->handle(Project::find($sample->project), KeyringType::Sample, $sample->id);

        $this->assertDatabaseHas('keyrings', ['type_id' => $sample->id, 'issued_to' => $this->holder->id]);
    }

    public function test_read_payloads_expose_editability_without_claiming_keyrings(): void
    {
        $sample = $this->sample();

        $this->getJson(route('samples.lookup.data', ['barcode' => $sample->barcode]))
            ->assertOk()
            ->assertJsonPath('data.read_only', false)
            ->assertJsonPath('data.reasons', [])
            ->assertJsonPath('data.keyring.locked', false);
        $this->assertDatabaseCount('keyrings', 0);

        Keyring::factory()->forResource(KeyringType::Sample, $sample->id)->heldBy($this->holder)->create();
        Keyring::factory()->forResource(KeyringType::Project, $sample->project)->heldBy($this->holder)->create();

        $this->getJson(route('samples.lookup.data', ['barcode' => $sample->barcode]))
            ->assertOk()
            ->assertJsonPath('data.read_only', true)
            ->assertJsonPath('data.reasons', ['keyring'])
            ->assertJsonPath('data.keyring.locked_by_name', 'Bob Bezet');
        $this->getJson(route('projects.search.samples.show', ['project' => $sample->project, 'sample' => $sample]))
            ->assertOk()
            ->assertJsonPath('data.read_only', true)
            ->assertJsonPath('data.reasons', ['keyring']);
        $this->getJson(route('projects.search.show', ['project' => $sample->project]))
            ->assertOk()
            ->assertJsonPath('data.read_only', true)
            ->assertJsonPath('data.keyring.locked_by_id', $this->holder->id);

        Project::query()->whereKey($sample->project)->update(['auth_status' => 1]);
        $this->getJson(route('projects.search.show', ['project' => $sample->project]))
            ->assertJsonPath('data.reasons', ['authorized', 'keyring']);
        $this->assertSame($this->holder->id, Keyring::query()->forResource(KeyringType::Sample, $sample->id)->value('issued_to'));
    }

    public function test_pages_receive_the_keyring_client_configuration(): void
    {
        Gate::define('keyrings.steal', fn () => true);

        $this->get(route('samples.lookup'))
            ->assertOk()
            ->assertViewIs('samples.lookup')
            ->assertSee('can_steal\\u0022:true', false)
            ->assertSee('user_id\\u0022:'.$this->user->id, false);
    }

    private function updateDescription(Sample $sample, string $value = 'Updated water sample')
    {
        return $this->patchJson(route('samples.lookup.details.update', ['project' => $sample->project, 'sample' => $sample->id]), [
            'source' => 'attribute', 'field' => 'description', 'value' => $value,
        ]);
    }

    private function presence(?bool $present): void
    {
        $this->mock(KeyringPresence::class)->allows('isPresent')->andReturn($present);
    }

    private function sample(array $project = []): Sample
    {
        $project = Project::create(['client' => 1, 'subclient' => 0, 'project_name' => 'Project water', 'lock_pass' => 'private', ...$project]);

        return Sample::create([
            'barcode' => '26091000', 'follow_no' => 1000, 'description' => 'Water sample',
            'sampling_method' => 1, 'date_registered' => '1788998400', 'registered_by' => 1,
            'client' => 1, 'subclient' => 0, 'project' => $project->id,
            'custom_fields' => '{}', 'predicted_end' => 1789171200, 'sample_innoculated' => '', 'analyses_data' => '[]',
        ]);
    }
}
