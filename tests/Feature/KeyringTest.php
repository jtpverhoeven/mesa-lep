<?php

namespace Tests\Feature;

use App\Actions\Keyrings\AcquireKeyring;
use App\Actions\Keyrings\StealKeyring;
use App\Actions\Users\UpdateUser;
use App\Events\KeyringChanged;
use App\Keyrings\KeyringPresence;
use App\Keyrings\KeyringType;
use App\Models\Keyring;
use App\Models\Project;
use App\Models\Sample;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class KeyringTest extends TestCase
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
        config(['keyrings.lifetime_minutes' => 30]);
        $this->travelTo(now()->startOfMinute());
        $this->user = User::factory()->create(['enabled' => 1, 'name' => 'Ada Analist']);
        $this->actingAs($this->user);
        Gate::define('samples.view', fn () => true);
        Gate::define('projects.view', fn () => true);
        Gate::define('reports.generate', fn () => true);
        Event::fake([KeyringChanged::class]);
    }

    public function test_a_free_keyring_is_acquired_and_broadcast(): void
    {
        $sample = $this->sample();
        $this->presence(null);

        $this->postJson(route('keyrings.store', ['type' => 'sample', 'typeId' => $sample->id]))
            ->assertOk()
            ->assertJsonPath('data.locked', false)
            ->assertJsonPath('data.lock_placed', true)
            ->assertJsonPath('data.locked_by_id', $this->user->id)
            ->assertJsonPath('data.locked_by_name', 'Ada Analist');

        $this->assertDatabaseHas('keyrings', [
            'type' => '0', 'type_id' => $sample->id, 'issued_to' => $this->user->id,
            'issued' => now()->timestamp, 'expires' => now()->timestamp + 1800,
        ]);
        Event::assertDispatched(KeyringChanged::class, fn (KeyringChanged $event): bool => $event->type === KeyringType::Sample
            && $event->typeId === $sample->id && $event->reason === 'acquired' && $event->holder['holder_id'] === $this->user->id
            && $event->broadcastOn()[0]->name === 'presence-keyrings.sample.'.$sample->id && $event->broadcastAs() === 'keyring.changed');
    }

    public function test_acquiring_an_own_keyring_refreshes_its_expiry_without_a_broadcast(): void
    {
        $sample = $this->sample();
        $keyring = Keyring::factory()->forResource(KeyringType::Sample, $sample->id)->heldBy($this->user)->create(['expires' => now()->timestamp + 10]);
        $this->presence(null);

        $this->postJson(route('keyrings.store', ['type' => 'sample', 'typeId' => $sample->id]))
            ->assertOk()->assertJsonPath('data.lock_placed', true)->assertJsonPath('data.lock_id', $keyring->id);

        $this->assertSame(now()->timestamp + 1800, $keyring->fresh()->expires);
        Event::assertNotDispatched(KeyringChanged::class);
    }

    #[DataProvider('takeoverCases')]
    public function test_a_keyring_held_by_another_user_follows_the_takeover_rule(?bool $holderPresent, bool $expired, bool $takenOver, ?string $reason, bool $extended): void
    {
        $holder = User::factory()->create(['enabled' => 1, 'name' => 'Bob Bezet']);
        $project = $this->project();
        $originalExpiry = $expired ? now()->timestamp - 60 : now()->timestamp + 60;
        Keyring::factory()->forResource(KeyringType::Project, $project->id)->heldBy($holder)->create(['expires' => $originalExpiry]);
        $this->presence($holderPresent);

        $response = $this->postJson(route('keyrings.store', ['type' => 'project', 'typeId' => $project->id]))->assertOk();

        $keyring = Keyring::query()->forResource(KeyringType::Project, $project->id)->sole();
        $this->assertSame($takenOver ? $this->user->id : $holder->id, $keyring->issued_to);
        $response->assertJsonPath('data.locked', ! $takenOver)
            ->assertJsonPath('data.locked_by_name', $takenOver ? 'Ada Analist' : 'Bob Bezet');
        $this->assertSame($takenOver || $extended ? now()->timestamp + 1800 : $originalExpiry, $keyring->expires);

        if ($reason === null) {
            Event::assertNotDispatched(KeyringChanged::class);
        } else {
            Event::assertDispatched(KeyringChanged::class, fn (KeyringChanged $event): bool => $event->reason === $reason
                && $event->previousHolderId === $holder->id && $event->holder['holder_id'] === $this->user->id);
        }
    }

    public static function takeoverCases(): array
    {
        return [
            'holder present before expiry' => [true, false, false, null, true],
            'holder present after expiry extends the keyring' => [true, true, false, null, true],
            'holder left before expiry' => [false, false, true, 'abandoned', false],
            'holder left after expiry' => [false, true, true, 'abandoned', false],
            'reverb unavailable before expiry' => [null, false, false, null, false],
            'reverb unavailable after expiry' => [null, true, true, 'expired', false],
        ];
    }

    public function test_acquire_retries_when_a_concurrent_request_inserts_the_same_keyring(): void
    {
        $sample = $this->sample();
        $this->presence(null);
        $raced = false;
        Keyring::creating(function (Keyring $keyring) use (&$raced): void {
            if (! $raced) {
                $raced = true;
                DB::table('keyrings')->insert([
                    'type' => (string) $keyring->type->value, 'type_id' => $keyring->type_id,
                    'issued' => now()->timestamp, 'issued_to' => $keyring->issued_to, 'expires' => $keyring->expires,
                ]);
            }
        });

        $keyring = app(AcquireKeyring::class)->claim(KeyringType::Sample, $sample->id, $this->user);

        $this->assertTrue($raced);
        $this->assertSame($this->user->id, $keyring->issued_to);
        $this->assertDatabaseCount('keyrings', 1);
    }

    public function test_keyring_requests_require_access_to_an_existing_resource(): void
    {
        $sample = $this->sample();
        $this->presence(null);

        $this->postJson(route('keyrings.store', ['type' => 'sample', 'typeId' => 99999]))->assertForbidden();
        $this->postJson('/keyrings/extern/'.$sample->project)->assertNotFound();
        Gate::define('reports.generate', fn () => false);
        $this->postJson(route('keyrings.store', ['type' => 'export', 'typeId' => $sample->project]))->assertForbidden();
        Gate::define('samples.view', fn () => false);
        Gate::define('projects.view', fn () => false);
        $this->postJson(route('keyrings.store', ['type' => 'sample', 'typeId' => $sample->id]))->assertForbidden();

        $this->assertDatabaseCount('keyrings', 0);
    }

    public function test_keyring_routes_require_authentication(): void
    {
        auth()->logout();

        $this->postJson(route('keyrings.store', ['type' => 'sample', 'typeId' => 1]))->assertUnauthorized();
        $this->deleteJson(route('keyrings.destroy', ['type' => 'sample', 'typeId' => 1]))->assertUnauthorized();
        $this->postJson(route('keyrings.steal', ['type' => 'sample', 'typeId' => 1]))->assertUnauthorized();
        $this->postJson(route('keyrings.release', ['keyring' => 1]))->assertUnauthorized();
    }

    public function test_users_can_only_release_their_own_keyrings(): void
    {
        $other = User::factory()->create(['enabled' => 1]);
        $own = Keyring::factory()->forResource(KeyringType::Sample, 10)->heldBy($this->user)->create();
        $foreign = Keyring::factory()->forResource(KeyringType::Sample, 11)->heldBy($other)->create();

        $this->deleteJson(route('keyrings.destroy', ['type' => 'sample', 'typeId' => 11]))->assertNoContent();
        $this->postJson(route('keyrings.release', ['keyring' => $foreign->id]))->assertNoContent();
        $this->assertModelExists($foreign);
        Event::assertNotDispatched(KeyringChanged::class);

        $this->deleteJson(route('keyrings.destroy', ['type' => 'sample', 'typeId' => 10]))->assertNoContent();
        $this->assertModelMissing($own);
        Event::assertDispatched(KeyringChanged::class, fn (KeyringChanged $event): bool => $event->holder === null
            && $event->reason === 'released' && $event->previousHolderId === $this->user->id && $event->typeId === 10);
    }

    public function test_the_page_hide_beacon_releases_by_keyring_id_with_a_form_token(): void
    {
        $keyring = Keyring::factory()->forResource(KeyringType::Export, 12)->heldBy($this->user)->create();

        $this->withSession(['_token' => 'beacon-token'])
            ->post(route('keyrings.release', ['keyring' => $keyring->id]), ['_token' => 'beacon-token'])
            ->assertNoContent();

        $this->assertModelMissing($keyring);
        Event::assertDispatched(KeyringChanged::class, fn (KeyringChanged $event): bool => $event->type === KeyringType::Export && $event->holder === null);
    }

    public function test_logging_out_releases_all_keyrings_of_the_user(): void
    {
        $other = User::factory()->create(['enabled' => 1]);
        Keyring::factory()->count(2)->heldBy($this->user)->create();
        $foreign = Keyring::factory()->heldBy($other)->create();

        $this->post(route('logout'))->assertRedirect();

        $this->assertGuest();
        $this->assertSame([$foreign->id], Keyring::query()->pluck('id')->all());
        Event::assertDispatchedTimes(KeyringChanged::class, 2);
    }

    public function test_disabling_a_user_releases_their_keyrings(): void
    {
        $analyst = User::factory()->create(['enabled' => 1]);
        Keyring::factory()->heldBy($analyst)->create();
        $data = ['first_name' => 'Ada', 'last_name' => 'Analist', 'email' => $analyst->email, 'locale' => 'nl', 'roles' => []];

        app(UpdateUser::class)->handle($analyst, [...$data, 'enabled' => true]);
        $this->assertDatabaseCount('keyrings', 1);

        app(UpdateUser::class)->handle($analyst, [...$data, 'enabled' => false]);
        $this->assertDatabaseCount('keyrings', 0);
    }

    public function test_stealing_requires_the_steal_permission(): void
    {
        $holder = User::factory()->create(['enabled' => 1]);
        $sample = $this->sample();
        Keyring::factory()->forResource(KeyringType::Sample, $sample->id)->heldBy($holder)->create();
        Gate::define('keyrings.steal', fn () => false);

        $this->postJson(route('keyrings.steal', ['type' => 'sample', 'typeId' => $sample->id]))->assertForbidden();

        $this->assertDatabaseHas('keyrings', ['type_id' => $sample->id, 'issued_to' => $holder->id]);
        $this->assertDatabaseCount('changetracker', 0);
    }

    #[DataProvider('stealTargets')]
    public function test_stealing_transfers_the_keyring_tracks_the_change_and_notifies_the_previous_holder(string $slug, KeyringType $type, string $column): void
    {
        $holder = User::factory()->create(['enabled' => 1, 'name' => 'Bob Bezet']);
        $sample = $this->sample();
        $typeId = $type === KeyringType::Sample ? $sample->id : (int) $sample->project;
        Keyring::factory()->forResource($type, $typeId)->heldBy($holder)->create();
        Gate::define('keyrings.steal', fn () => true);
        $this->presence(true);

        $this->postJson(route('keyrings.steal', ['type' => $slug, 'typeId' => $typeId]))
            ->assertOk()->assertJsonPath('data.lock_placed', true)->assertJsonPath('data.locked_by_id', $this->user->id);

        $this->assertDatabaseHas('keyrings', ['type' => (string) $type->value, 'type_id' => $typeId, 'issued_to' => $this->user->id]);
        $this->assertDatabaseHas('changetracker', [
            'type' => (string) StealKeyring::CHANGE_TYPE, $column => $typeId, 'user_id' => $this->user->id,
            'event' => "Vergrendeling ({$slug}) overgenomen", 'from' => 'Bob Bezet', 'to' => 'Ada Analist',
        ]);
        Event::assertDispatched(KeyringChanged::class, fn (KeyringChanged $event): bool => $event->reason === 'stolen'
            && $event->previousHolderId === $holder->id && $event->holder['holder_id'] === $this->user->id);
    }

    public static function stealTargets(): array
    {
        return [
            'sample' => ['sample', KeyringType::Sample, 'sample'],
            'project' => ['project', KeyringType::Project, 'project'],
            'export' => ['export', KeyringType::Export, 'project'],
        ];
    }

    public function test_a_concurrent_steal_that_changed_the_holder_first_wins(): void
    {
        $holder = User::factory()->create(['enabled' => 1]);
        $rival = User::factory()->create(['enabled' => 1, 'name' => 'Rita Rivaal']);
        $sample = $this->sample();
        $keyring = Keyring::factory()->forResource(KeyringType::Sample, $sample->id)->heldBy($holder)->create();
        $raced = false;
        Keyring::retrieved(function (Keyring $retrieved) use (&$raced, $keyring, $rival): void {
            if (! $raced && $retrieved->id === $keyring->id) {
                $raced = true;
                DB::table('keyrings')->where('id', $keyring->id)->update(['issued_to' => $rival->id]);
            }
        });

        $status = app(StealKeyring::class)->handle(KeyringType::Sample, $sample->id, $this->user);

        $this->assertTrue($raced);
        $this->assertTrue($status['locked']);
        $this->assertSame('Rita Rivaal', $status['locked_by_name']);
        $this->assertSame($rival->id, $keyring->fresh()->issued_to);
        $this->assertDatabaseCount('changetracker', 0);
        Event::assertNotDispatched(KeyringChanged::class);
    }

    public function test_stealing_a_free_or_own_keyring_acquires_it_without_tracking_a_steal(): void
    {
        $sample = $this->sample();
        Gate::define('keyrings.steal', fn () => true);
        $this->presence(null);

        $this->postJson(route('keyrings.steal', ['type' => 'sample', 'typeId' => $sample->id]))->assertOk()->assertJsonPath('data.lock_placed', true);
        $this->postJson(route('keyrings.steal', ['type' => 'sample', 'typeId' => $sample->id]))->assertOk()->assertJsonPath('data.lock_placed', true);

        $this->assertDatabaseCount('keyrings', 1);
        $this->assertDatabaseCount('changetracker', 0);
        Event::assertDispatched(KeyringChanged::class, fn (KeyringChanged $event): bool => $event->reason === 'acquired');
        Event::assertNotDispatched(KeyringChanged::class, fn (KeyringChanged $event): bool => $event->reason === 'stolen');
    }

    public function test_the_sweep_releases_expired_keyrings_of_absent_holders_and_extends_present_ones(): void
    {
        $present = User::factory()->create(['enabled' => 1]);
        $absent = User::factory()->create(['enabled' => 1]);
        $extended = Keyring::factory()->forResource(KeyringType::Sample, 1)->heldBy($present)->expired()->create();
        $released = Keyring::factory()->forResource(KeyringType::Sample, 2)->heldBy($absent)->expired()->create();
        $current = Keyring::factory()->forResource(KeyringType::Sample, 3)->heldBy($absent)->create();
        $this->mock(KeyringPresence::class)->allows('isPresent')
            ->andReturnUsing(fn (KeyringType $type, int $typeId, int $userId): bool => $userId === $present->id);

        $this->artisan('keyrings:sweep')->expectsOutputToContain('1')->assertSuccessful();

        $this->assertSame(now()->timestamp + 1800, $extended->fresh()->expires);
        $this->assertModelMissing($released);
        $this->assertModelExists($current);
        Event::assertDispatchedTimes(KeyringChanged::class, 1);
        Event::assertDispatched(KeyringChanged::class, fn (KeyringChanged $event): bool => $event->typeId === 2
            && $event->reason === 'expired' && $event->holder === null && $event->previousHolderId === $absent->id);
    }

    public function test_the_sweep_releases_expired_keyrings_when_reverb_is_unavailable(): void
    {
        $keyring = Keyring::factory()->expired()->create();
        $this->presence(null);

        $this->artisan('keyrings:sweep')->assertSuccessful();

        $this->assertModelMissing($keyring);
    }

    public function test_the_keyring_lifetime_falls_back_to_the_session_lifetime(): void
    {
        config(['keyrings.lifetime_minutes' => null, 'session.lifetime' => 120]);

        $this->assertSame(now()->timestamp + 7200, Keyring::nextExpiry());
    }

    public function test_holder_avatars_are_served_to_users_who_can_view_samples_or_projects(): void
    {
        Storage::fake('local');
        $holder = User::factory()->create(['enabled' => 1]);
        $holder->profile()->create(['first_name' => 'Bob', 'last_name' => 'Bezet', 'locale' => 'nl', 'avatar_path' => 'avatars/bob.png']);

        $this->get(route('users.avatar', $holder))->assertNotFound();

        Storage::disk('local')->put('avatars/bob.png', 'png-bytes');
        $this->get(route('users.avatar', $holder))->assertOk()->assertHeader('Content-Type', 'image/png');
        $this->assertStringEndsWith('/users/'.$holder->id.'/avatar', Keyring::factory()->heldBy($holder)->create()->holderPayload()['avatar_url']);

        Gate::define('samples.view', fn () => false);
        Gate::define('projects.view', fn () => false);
        $this->get(route('users.avatar', $holder))->assertForbidden();
    }

    private function presence(?bool $present): void
    {
        $this->mock(KeyringPresence::class)->allows('isPresent')->andReturn($present);
    }

    private function project(): Project
    {
        return Project::create(['client' => 1, 'subclient' => 0, 'project_name' => 'Project water', 'lock_pass' => 'private']);
    }

    private function sample(): Sample
    {
        return Sample::create([
            'barcode' => '26091000', 'follow_no' => 1000, 'description' => 'Water sample',
            'sampling_method' => 1, 'date_registered' => '1788998400', 'registered_by' => 1,
            'client' => 1, 'subclient' => 0, 'project' => $this->project()->id,
            'custom_fields' => '{}', 'predicted_end' => 1789171200, 'sample_innoculated' => '', 'analyses_data' => '[]',
        ]);
    }
}
