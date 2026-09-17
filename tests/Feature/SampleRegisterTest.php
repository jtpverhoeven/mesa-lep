<?php

namespace Tests\Feature;

use App\Models\Cvar;
use App\Models\Project;
use App\Models\Sample;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class SampleRegisterTest extends TestCase
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
        Gate::define('samples.list', fn () => true);
    }

    public function test_data_returns_per_user_legacy_location_settings(): void
    {
        $userId = (string) auth()->id();
        Cvar::create([
            'cvar' => 'MESA_CURRENT_BIN',
            'value' => json_encode([$userId => 'Q']),
            'default' => 'A',
            'description' => 'Storage bins',
        ]);
        Cvar::create([
            'cvar' => 'MESA_CURRENT_DILUTION',
            'value' => json_encode([$userId => 'DIL']),
            'default' => '{}',
            'description' => 'Dilution stations',
        ]);

        $response = $this->getJson(route('samples.register.data', ['type' => '1']));

        $response->assertOk()
            ->assertJsonPath('data.settings.storage', 'Q')
            ->assertJsonPath('data.settings.dilution_at', 'DIL');
    }

    public function test_settings_are_persisted_and_used_when_a_sample_is_registered(): void
    {
        $sample = $this->sample('26091000');
        Queue::fake();

        $this->withSession(['_token' => 'register-test-token'])
            ->withHeader('X-CSRF-TOKEN', 'register-test-token')
            ->patchJson(route('samples.register.settings'), [
                'storage' => 'T',
                'dilution_at' => 'DIL',
            ])->assertOk();

        $response = $this->postJson(route('samples.register.inoculation'), [
            'barcode' => $sample->barcode,
        ]);

        $response->assertOk()
            ->assertJsonPath('data.sample.stored_in', 'T')
            ->assertJsonPath('data.sample.diluted_at', 'DIL');
        $this->assertSame('T', json_decode((string) Cvar::where('cvar', 'MESA_CURRENT_BIN')->value('value'), true)[(string) auth()->id()]);
        $this->assertSame('T', $sample->fresh()->stored_in);
        $this->assertSame('DIL', $sample->fresh()->diluted_at);
        $this->assertNotSame('', $sample->fresh()->sample_innoculated);
    }

    public function test_started_sample_conditions_can_be_updated(): void
    {
        $sample = $this->sample('26091001', null, '1788998400');
        Queue::fake();

        $response = $this->withSession(['_token' => 'register-test-token'])
            ->withHeader('X-CSRF-TOKEN', 'register-test-token')
            ->patchJson(route('samples.register.conditions', ['sample' => $sample]), [
                'innoc' => '01-01-2026 12:34',
                'storage' => 'B',
                'diluted_at' => 'DIL',
            ]);

        $response->assertOk()
            ->assertJsonPath('data.sample.inoculation_date', '01-01-2026')
            ->assertJsonPath('data.sample.inoculation_time', '12:34')
            ->assertJsonPath('data.sample.stored_in', 'B')
            ->assertJsonPath('data.sample.diluted_at', 'DIL');
        $this->assertSame('B', $sample->fresh()->stored_in);
        $this->assertSame('DIL', $sample->fresh()->diluted_at);
        $this->assertNotSame('1788998400', $sample->fresh()->sample_innoculated);
    }

    public function test_conditions_cannot_be_updated_for_a_locked_project(): void
    {
        $sample = $this->sample('26091002', null, '1788998400');
        Project::query()->whereKey($sample->project)->update(['locked' => 1]);

        $this->withSession(['_token' => 'register-test-token'])
            ->withHeader('X-CSRF-TOKEN', 'register-test-token')
            ->patchJson(route('samples.register.conditions', ['sample' => $sample]), [
                'innoc' => '01-01-2026 12:34',
                'storage' => 'B',
                'diluted_at' => 'DIL',
            ])->assertUnprocessable();

        $this->assertSame('1788998400', $sample->fresh()->sample_innoculated);
    }

    private function sample(string $barcode, ?int $projectId = null, string $inoculated = ''): Sample
    {
        $projectId ??= Project::create([
            'client' => 1,
            'subclient' => 0,
            'project_name' => 'Project water',
            'lock_pass' => 'private',
        ])->id;

        return Sample::create([
            'barcode' => $barcode,
            'follow_no' => 1000,
            'description' => 'Water sample',
            'sampling_method' => 1,
            'date_registered' => '1788998400',
            'registered_by' => 1,
            'client' => 1,
            'subclient' => 0,
            'project' => $projectId,
            'custom_fields' => '{}',
            'predicted_end' => 1789171200,
            'sample_innoculated' => $inoculated,
            'stored_in' => 'A',
            'diluted_at' => '1',
            'analyses_data' => '[]',
        ]);
    }
}
