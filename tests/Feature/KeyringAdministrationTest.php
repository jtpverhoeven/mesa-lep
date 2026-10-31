<?php

namespace Tests\Feature;

use App\Actions\Keyrings\StealKeyring;
use App\Events\KeyringChanged;
use App\Keyrings\KeyringType;
use App\Models\Keyring;
use App\Models\Project;
use App\Models\Sample;
use App\Models\User;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class KeyringAdministrationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');
        foreach (glob(database_path('migrations/*.php')) as $path) {
            (require $path)->up();
        }
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function test_administrators_can_view_the_keyring_management_page(): void
    {
        $administrator = $this->administrator();

        $this->actingAs($administrator)
            ->get(route('keyrings.admin.index'))
            ->assertSee('Vergrendelingen')
            ->assertSee('<keyring-administration', false);
    }

    public function test_administrators_can_load_current_and_expired_keyrings(): void
    {
        $administrator = $this->administrator();
        $holder = User::factory()->create(['enabled' => true, 'name' => 'Bob Bezet']);
        $expired = Keyring::factory()->forResource(KeyringType::Sample, 12)->heldBy($holder)->expired()->create();
        Keyring::factory()->forResource(KeyringType::Project, 34)->heldBy($holder)->create();

        $this->actingAs($administrator)
            ->getJson(route('keyrings.admin.data'))
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.id', $expired->id)
            ->assertJsonPath('data.0.type', 'sample')
            ->assertJsonPath('data.0.expired', true)
            ->assertJsonPath('data.0.holder_name', 'Bob Bezet')
            ->assertJsonPath('data.1.type', 'project')
            ->assertJsonPath('data.1.expired', false);
    }

    public function test_users_without_keyring_management_permission_cannot_view_or_load_keyrings(): void
    {
        $user = $this->userWithPermissions(['administration.access', 'settings.advanced', 'keyrings.steal']);

        $this->actingAs($user)->get(route('keyrings.admin.index'))->assertForbidden();
        $this->getJson(route('keyrings.admin.data'))->assertForbidden();
    }

    public function test_guests_cannot_load_keyrings(): void
    {
        $this->getJson(route('keyrings.admin.data'))->assertUnauthorized();
    }

    public function test_only_enabled_administrators_can_join_the_admin_keyring_channel(): void
    {
        $administrator = $this->administrator();
        $disabledAdministrator = $this->administrator(enabled: false);
        $user = $this->userWithPermissions(['administration.access', 'keyrings.steal']);
        $lockManager = $this->userWithPermissions(['keyrings.manage']);
        $authorize = Broadcast::getChannels()->get('keyrings.admin');

        $this->assertSame(true, $authorize($administrator));
        $this->assertFalse($authorize($disabledAdministrator));
        $this->assertFalse($authorize($user));
        $this->assertFalse($authorize($lockManager));
    }

    public function test_administrators_can_force_delete_keyrings_and_broadcast_the_change(): void
    {
        $administrator = $this->administrator(name: 'Ada Beheer');
        $holder = User::factory()->create(['enabled' => true, 'name' => 'Bob Bezet']);
        $sample = $this->sample();
        $keyring = Keyring::factory()->forResource(KeyringType::Sample, $sample->id)->heldBy($holder)->create();
        Event::fake([KeyringChanged::class]);

        $this->actingAs($administrator)
            ->deleteJson(route('keyrings.admin.destroy', ['keyring' => $keyring->id]))
            ->assertNoContent();

        $this->assertModelMissing($keyring);
        $this->assertDatabaseHas('changetracker', [
            'type' => (string) StealKeyring::CHANGE_TYPE,
            'project' => $sample->project,
            'sample' => $sample->id,
            'user_id' => $administrator->id,
            'event' => 'Vergrendeling (sample) geforceerd verwijderd',
            'from' => 'Bob Bezet',
            'to' => 'Ada Beheer',
        ]);
        Event::assertDispatched(KeyringChanged::class, function (KeyringChanged $event) use ($keyring, $holder): bool {
            $channels = $event->broadcastOn();

            return $event->type === KeyringType::Sample
                && $event->typeId === $keyring->type_id
                && $event->holder === null
                && $event->reason === 'force_deleted'
                && $event->previousHolderId === $holder->id
                && count($channels) === 2
                && $channels[0]->name === 'presence-keyrings.sample.'.$keyring->type_id
                && $channels[1] instanceof PrivateChannel
                && $channels[1]->name === 'private-keyrings.admin';
        });
    }

    public function test_users_without_keyring_management_permission_cannot_force_delete_keyrings(): void
    {
        $user = $this->userWithPermissions(['administration.access', 'keyrings.steal']);
        $holder = User::factory()->create(['enabled' => true]);
        $keyring = Keyring::factory()->forResource(KeyringType::Sample, 12)->heldBy($holder)->create();
        Event::fake([KeyringChanged::class]);

        $this->actingAs($user)
            ->deleteJson(route('keyrings.admin.destroy', ['keyring' => $keyring->id]))
            ->assertForbidden();

        $this->assertModelExists($keyring);
        $this->assertDatabaseCount('changetracker', 0);
        Event::assertNotDispatched(KeyringChanged::class);
    }

    private function administrator(string $name = 'Ada Beheer', bool $enabled = true): User
    {
        $role = Role::findOrCreate('administrator', 'web');
        $role->givePermissionTo(Permission::findOrCreate('keyrings.manage', 'web'));
        $user = User::factory()->create(['enabled' => $enabled, 'name' => $name]);
        $user->assignRole($role);

        return $user;
    }

    private function userWithPermissions(array $permissions): User
    {
        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        $role = Role::findOrCreate('keyring-administration-test-role', 'web');
        $role->syncPermissions($permissions);
        $user = User::factory()->create(['enabled' => true]);
        $user->assignRole($role);

        return $user;
    }

    private function sample(): Sample
    {
        $project = Project::create([
            'client' => 1,
            'subclient' => 0,
            'project_name' => 'Project water',
            'lock_pass' => 'private',
        ]);

        return Sample::create([
            'barcode' => '26091000',
            'follow_no' => 1000,
            'description' => 'Water sample',
            'sampling_method' => 1,
            'date_registered' => '1788998400',
            'registered_by' => 1,
            'client' => 1,
            'subclient' => 0,
            'project' => $project->id,
            'custom_fields' => '{}',
            'predicted_end' => 1789171200,
            'sample_innoculated' => '',
            'analyses_data' => '[]',
        ]);
    }
}
