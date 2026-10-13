<?php

namespace Tests\Feature;

use App\Jobs\RunPrintProfileJob;
use App\Models\LabelDesign;
use App\Models\Printer;
use App\Models\PrintProfile;
use App\Models\Sample;
use App\Models\User;
use App\Support\Printing\LabelPrinterTransport;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PrintProfileAdministrationTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_authorized_users_can_create_edit_list_and_delete_profiles_in_the_legacy_table(): void
    {
        $existing = PrintProfile::factory()->create();
        $this->actingAs($this->userWithPermissions());
        $script = "# legacy line endings\r\n10 LOG start\r\n20 EXIT";

        $this->post(route('print-profiles.store'), ['name' => 'Water', 'profile' => $script, 'id' => $existing->id])
            ->assertSessionHasNoErrors()->assertRedirect();

        $created = PrintProfile::query()->whereKeyNot($existing->id)->sole();
        $this->assertDatabaseHas('printing', ['id' => $created->id, 'name' => 'Water', 'profile' => $script]);
        $this->get(route('print-profiles.index'))->assertViewHas('printProfiles', fn ($profiles): bool => $profiles->count() === 2)->assertSee('Water');
        $this->get(route('print-profiles.create'))->assertViewHas('printProfile', fn (PrintProfile $profile): bool => ! $profile->exists);
        $this->get(route('print-profiles.edit', $created))->assertViewHas('printProfile', fn (PrintProfile $profile): bool => $profile->profile === $script);
        $this->put(route('print-profiles.update', $created), ['name' => 'Nieuw', 'profile' => '10 EXIT', 'id' => $existing->id])
            ->assertRedirect(route('print-profiles.edit', $created))->assertSessionHasNoErrors();
        $this->assertDatabaseHas('printing', ['id' => $created->id, 'name' => 'Nieuw', 'profile' => '10 EXIT']);
        $this->assertSame($existing->name, $existing->fresh()->name);

        $this->delete(route('print-profiles.destroy', $created))->assertRedirect(route('print-profiles.index'));

        $this->assertModelMissing($created);
        $this->assertModelExists($existing);
    }

    public function test_script_errors_preserve_the_draft_without_writing(): void
    {
        $profile = PrintProfile::factory()->create();
        $this->actingAs($this->userWithPermissions());

        $this->from(route('print-profiles.edit', $profile))->put(route('print-profiles.update', $profile), ['name' => 'Draft', 'profile' => '10 GOTO 99'])
            ->assertRedirect(route('print-profiles.edit', $profile))->assertSessionHasErrors(['profile' => 'Regel 10: doelregel 99 bestaat niet.'])
            ->assertSessionHasInput('profile', '10 GOTO 99');

        $this->assertSame('10 EXIT', $profile->fresh()->profile);
    }

    #[DataProvider('invalidProfiles')]
    public function test_invalid_profile_fields_return_422_without_creating_records(array $payload, array $fields): void
    {
        $this->actingAs($this->userWithPermissions());

        $this->postJson(route('print-profiles.store'), $payload)->assertUnprocessable()->assertJsonValidationErrors($fields);

        $this->assertDatabaseCount('printing', 0);
    }

    #[DataProvider('endpoints')]
    public function test_guests_cannot_access_profile_endpoints(string $method, string $path): void
    {
        $profile = PrintProfile::factory()->create();
        Queue::fake();

        $this->call($method, '/admin/print-profiles'.str_replace('__ID__', (string) $profile->id, $path))->assertRedirect(route('login'));

        $this->assertModelExists($profile);
        Queue::assertNothingPushed();
    }

    #[DataProvider('endpoints')]
    public function test_users_without_print_settings_permission_receive_403(string $method, string $path): void
    {
        $profile = PrintProfile::factory()->create();
        $this->actingAs($this->userWithPermissions(['administration.access']));
        Queue::fake();

        $this->call($method, '/admin/print-profiles'.str_replace('__ID__', (string) $profile->id, $path))->assertForbidden();

        $this->assertModelExists($profile);
        Queue::assertNothingPushed();
    }

    public function test_dry_run_returns_rendered_labels_for_the_unsaved_draft_without_queueing_or_saving(): void
    {
        $sample = $this->sample();
        $design = LabelDesign::factory()->create(['zpl' => '^XA^FD{sample_barcode}^FS^XZ']);
        $printer = Printer::factory()->create(['name' => 'Balie']);
        $this->actingAs($this->userWithPermissions());
        Queue::fake();
        $this->mock(LabelPrinterTransport::class, fn (MockInterface $mock) => $mock->shouldNotReceive('send'));

        $this->postJson(route('print-profiles.test'), ['barcode' => $sample->barcode, 'profile' => "10 SAMPLE PRINT $design->id PRINTER $printer->id COPIES 3"])
            ->assertJsonPath('total_labels', 3)->assertJsonPath('printer_count', 1)
            ->assertJsonPath('labels.0.printer_name', 'Balie')->assertJsonPath('labels.0.label_name', $design->name)
            ->assertJsonPath('labels.0.zpl', '^XA^FDM260001^FS^XZ');

        $this->assertDatabaseCount('printing', 0);
        Queue::assertNothingPushed();
    }

    public function test_dry_run_reports_unknown_barcodes_and_script_errors_as_422(): void
    {
        $sample = $this->sample();
        $this->actingAs($this->userWithPermissions());
        Queue::fake();

        $this->postJson(route('print-profiles.test'), ['barcode' => 'unknown', 'profile' => '10 EXIT'])
            ->assertUnprocessable()->assertJsonPath('errors.barcode.0', 'Geen monster gevonden met deze barcode.');
        $this->postJson(route('print-profiles.test'), ['barcode' => $sample->barcode, 'profile' => '10 GOTO 999'])
            ->assertUnprocessable()->assertJsonPath('errors.profile.0', 'Regel 10: doelregel 999 bestaat niet.');

        Queue::assertNothingPushed();
    }

    public function test_run_queues_the_saved_script_and_ignores_posted_script_content(): void
    {
        $sample = $this->sample();
        $profile = PrintProfile::factory()->create();
        $this->actingAs($this->userWithPermissions());
        Queue::fake([RunPrintProfileJob::class]);

        $this->postJson(route('print-profiles.run', $profile), ['barcode' => $sample->barcode, 'profile' => '10 EXEC evil'])
            ->assertAccepted()->assertJsonPath('message', 'Printprofiel staat in de wachtrij.');

        Queue::assertPushed(RunPrintProfileJob::class, fn (RunPrintProfileJob $job): bool => $job->profileId === $profile->id && $job->profileScript === '10 EXIT' && $job->sampleId === $sample->id);
    }

    public function test_missing_profiles_return_404_and_do_not_queue(): void
    {
        $this->actingAs($this->userWithPermissions());
        Queue::fake();

        $this->get(route('print-profiles.edit', 999999))->assertNotFound();
        $this->postJson(route('print-profiles.run', 999999), ['barcode' => 'unknown'])->assertNotFound();

        Queue::assertNothingPushed();
    }

    public function test_profile_names_and_scripts_are_escaped_in_views(): void
    {
        $unsafe = '</script><script>alert("profile")</script>';
        $profile = PrintProfile::factory()->create(['name' => $unsafe, 'profile' => '10 LOG '.$unsafe]);
        $this->actingAs($this->userWithPermissions());

        $this->get(route('print-profiles.index'))->assertDontSee($unsafe, false);
        $this->get(route('print-profiles.edit', $profile))->assertDontSee($unsafe, false);
    }

    public static function invalidProfiles(): array
    {
        return [
            'missing fields' => [[], ['name', 'profile']],
            'name too long' => [['name' => str_repeat('a', 65), 'profile' => '10 EXIT'], ['name']],
            'script is not text' => [['name' => 'Water', 'profile' => []], ['profile']],
            'script too long' => [['name' => 'Water', 'profile' => str_repeat('a', 65536)], ['profile']],
        ];
    }

    public static function endpoints(): array
    {
        return [['GET', ''], ['GET', '/create'], ['POST', ''], ['GET', '/__ID__/edit'], ['PUT', '/__ID__'], ['DELETE', '/__ID__'], ['POST', '/test'], ['POST', '/__ID__/run']];
    }

    private function userWithPermissions(array $permissions = ['administration.access', 'labels.print-settings']): User
    {
        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        $role = Role::findOrCreate('print-profile-test-role', 'web');
        $role->syncPermissions($permissions);
        $user = User::factory()->create(['enabled' => 1]);
        $user->assignRole($role);

        return $user;
    }

    private function sample(): Sample
    {
        return Sample::create([
            'barcode' => 'M260001', 'follow_no' => 1, 'description' => 'Water', 'sampling_method' => 0,
            'date_registered' => '1788998400', 'registered_by' => 1, 'client' => 0, 'subclient' => 0,
            'project' => 0, 'custom_fields' => '{}', 'predicted_end' => 1789171200,
            'sample_innoculated' => '', 'stored_in' => 'A', 'diluted_at' => '1', 'analyses_data' => '[]',
        ])->refresh();
    }
}
