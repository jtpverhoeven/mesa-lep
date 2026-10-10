<?php

namespace Tests\Feature;

use App\Models\Cvar;
use App\Models\Project;
use App\Models\Sample;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\DataProvider;
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

    #[DataProvider('sampleTypes')]
    public function test_inoculating_one_sample_moves_its_project_from_received_to_running(string $sampleType): void
    {
        $this->freezeTime();
        Gate::define('projects.view', fn (): bool => true);
        $sample = $this->sample('26091010');
        $sample->update(['sample_type' => $sampleType]);
        $otherSample = $this->sample('26091011', $sample->project);
        $unrelatedSample = $this->sample('26091012');
        Queue::fake();

        $this->getJson(route('projects.overview.received.data'))->assertOk()
            ->assertJsonPath('total', 2)->assertJsonPath('data.0.id', $sample->project);
        $this->getJson(route('projects.overview.running.data'))->assertOk()->assertJsonPath('total', 0);

        $this->postJson(route('samples.register.inoculation'), ['barcode' => $sample->barcode])
            ->assertOk()->assertJsonPath('data.sample.id', $sample->id);

        $this->assertSame((string) now()->timestamp, $sample->fresh()->sample_innoculated);
        $this->assertSame('', $otherSample->fresh()->sample_innoculated);
        $this->assertDatabaseHas('projects', ['id' => $sample->project, 'started' => 1]);
        $this->assertDatabaseHas('projects', ['id' => $unrelatedSample->project, 'started' => 0]);
        $this->getJson(route('projects.overview.received.data'))->assertOk()
            ->assertJsonPath('total', 1)->assertJsonPath('data.0.id', $unrelatedSample->project);
        $this->getJson(route('projects.overview.running.data'))->assertOk()
            ->assertJsonPath('total', 1)->assertJsonPath('data.0.id', $sample->project)
            ->assertJsonPath('data.0.status', 'Lopend');
    }

    public static function sampleTypes(): array
    {
        return [
            'standard' => ['S'],
            'legionella' => ['L'],
            'rodac' => ['R'],
        ];
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
