<?php

namespace Tests\Feature;

use App\Actions\Sidebar\CalculateSidebarCounts;
use App\Actions\Sidebar\GetSidebarCounts;
use App\Actions\Sidebar\RefreshSidebarCounts;
use App\Events\SidebarCountsUpdated;
use App\Models\AssuranceForm;
use App\Models\Client;
use App\Models\Cvar;
use App\Models\Project;
use App\Models\Sample;
use App\Models\SampleBuffer;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class SidebarCountsTest extends TestCase
{
    private Client $client;

    protected function setUp(): void
    {
        parent::setUp();

        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');
        foreach (glob(database_path('migrations/*.php')) as $path) {
            (require $path)->up();
        }

        Carbon::setTestNow(Carbon::parse('2026-11-15 12:00:00', 'Europe/Amsterdam'));
        $this->client = Client::create(['name' => 'Waterbedrijf', 'active' => 1]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_counts_follow_the_lists_they_link_to(): void
    {
        $this->buffer(['authorized' => 0, 'tht' => 0]);
        $this->buffer(['authorized' => 0, 'tht' => 1]);
        $this->buffer(['authorized' => 1, 'tht' => 1, 'tht_date' => '2026-11-15']);
        $this->buffer(['authorized' => 1, 'tht' => 1, 'tht_date' => '2026-11-17']);
        $this->buffer(['authorized' => 1, 'tht' => 1, 'tht_date' => '2026-11-10']);

        $analysed = $this->sample(['sample_innoculated' => '1']);
        DB::table('sampleanalysis')->insert([
            'profile_group' => 1, 'sample' => $analysed->id, 'follow_number' => 1, 'profile' => 1,
            'assay' => 1, 'assay_base' => 1, 'predicted_end' => 1, 'project' => $analysed->project,
        ]);
        $this->sample(['isEmpty' => 0, 'sample_innoculated' => '1']);
        $this->sample(['client' => 999, 'sample_innoculated' => '1']);
        $this->sample(['sample_innoculated' => '1']);
        $this->sample(['isEmpty' => 0, 'date_registered' => '1700000000']);

        foreach ([
            ['started' => 0], ['started' => 0], ['started' => 1], ['started' => 1, 'is_ready' => 1],
            ['started' => 1, 'auth_status' => 1], ['started' => 1, 'auth_status' => 1, 'rap_stat' => 1],
            ['started' => 1, 'locked' => 1], ['started' => 1, 'locked' => 1, 'auth_status' => 1],
        ] as $state) {
            $this->project($state);
        }

        $counts = app(CalculateSidebarCounts::class)->handle();

        $this->assertSame([
            'buffer' => 2,
            'tht' => 2,
            'awaiting_analysis' => 1,
            'not_inoculated' => 1,
            'received' => 2,
            'running' => 2,
            'completed' => 1,
            'authorized' => 2,
            'blocked' => 1,
            'assurance' => 0,
        ], $counts);
    }

    public function test_not_inoculated_counts_registrations_from_before_yesterday_without_inoculation(): void
    {
        $beforeYesterday = Carbon::parse('2026-11-13 23:59:00', 'Europe/Amsterdam')->timestamp;
        $yesterday = Carbon::parse('2026-11-14 08:00:00', 'Europe/Amsterdam')->timestamp;

        foreach (['', '0'] as $inoculation) {
            $this->sample(['date_registered' => (string) $beforeYesterday, 'sample_innoculated' => $inoculation]);
        }
        $this->sample(['date_registered' => (string) $beforeYesterday, 'sample_innoculated' => '1788998400']);
        $this->sample(['date_registered' => (string) $yesterday, 'sample_innoculated' => '']);

        $this->assertSame(2, app(CalculateSidebarCounts::class)->handle()['not_inoculated']);
    }

    public function test_assurance_counts_incomplete_forms_past_the_cooldown_without_a_lower_limit(): void
    {
        Cvar::create(['cvar' => 'BORG_TIME_BEFORE_CHECK', 'value' => '10', 'default' => '10', 'description' => 'Cooldown']);
        $this->form(Carbon::now()->subDays(20), false);
        $this->form(Carbon::now()->subDays(100), false);
        $this->form(Carbon::now()->subDays(5), false);
        $this->form(Carbon::now()->subDays(20), true);

        $this->assertSame(2, app(CalculateSidebarCounts::class)->handle()['assurance']);
    }

    public function test_refresh_caches_the_counts_and_broadcasts_only_when_they_changed(): void
    {
        Event::fake([SidebarCountsUpdated::class]);
        $this->buffer(['authorized' => 0, 'tht' => 0]);

        $first = app(RefreshSidebarCounts::class)->handle();
        app(RefreshSidebarCounts::class)->handle();

        $this->assertSame(1, $first['buffer']);
        $this->assertSame($first, Cache::get(GetSidebarCounts::CACHE_KEY));
        Event::assertDispatchedTimes(SidebarCountsUpdated::class, 1);

        $this->buffer(['authorized' => 0, 'tht' => 0]);
        app(RefreshSidebarCounts::class)->handle();

        Event::assertDispatchedTimes(SidebarCountsUpdated::class, 2);
        Event::assertDispatched(SidebarCountsUpdated::class, fn (SidebarCountsUpdated $event): bool => $event->counts['buffer'] === 2);
    }

    public function test_pages_render_the_cached_counts_without_waiting_for_a_refresh(): void
    {
        Cache::put(GetSidebarCounts::CACHE_KEY, ['buffer' => 7], GetSidebarCounts::TTL_SECONDS);
        $this->buffer(['authorized' => 0, 'tht' => 0]);

        $this->actingAs(User::factory()->create(['enabled' => 1]))
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('\u0022buffer\u0022:7', false);
    }

    public function test_only_enabled_users_can_listen_to_the_counts_channel(): void
    {
        $authorize = Broadcast::getChannels()->get('sidebar-counts');

        $this->assertTrue($authorize(User::factory()->create(['enabled' => 1])));
        $this->assertFalse($authorize(User::factory()->create(['enabled' => 0])));
    }

    public function test_not_inoculated_list_returns_the_samples_behind_the_badge(): void
    {
        Gate::define('samples.list', fn (): bool => true);
        $this->actingAs(User::factory()->create(['enabled' => 1]));
        $open = $this->sample(['barcode' => 'OPEN', 'date_registered' => '1700000000', 'sample_innoculated' => '']);
        $this->sample(['barcode' => 'DONE', 'date_registered' => '1700000000', 'sample_innoculated' => '1700003600']);

        $this->getJson(route('samples.register.not-inoculated'))
            ->assertOk()
            ->assertJsonPath('data.total', 1)
            ->assertJsonPath('data.samples.0.id', $open->id)
            ->assertJsonPath('data.samples.0.barcode', 'OPEN');
    }

    /** @param array<string, mixed> $attributes */
    private function buffer(array $attributes): SampleBuffer
    {
        return SampleBuffer::forceCreate([
            'client' => $this->client->id, 'source' => 1, 'project' => 'PRJ-1', 'sampling_date' => '14-11-2026',
            'sampling_method' => 1, 'sample_name' => 'Water', 'sample_details' => '', 'tht' => 0,
            'meta' => '[]', 'analyses_selected' => '[]', 'authorized' => 0, ...$attributes,
        ]);
    }

    /** @param array<string, mixed> $attributes */
    private function project(array $attributes): Project
    {
        return Project::forceCreate([
            'client' => $this->client->id, 'subclient' => 0, 'project_name' => 'Project',
            'started' => 0, 'is_ready' => 0, 'auth_status' => 0, 'rap_stat' => 0, 'locked' => 0, ...$attributes,
        ]);
    }

    /** @param array<string, mixed> $attributes */
    private function sample(array $attributes = []): Sample
    {
        static $number = 0;
        $number++;

        return Sample::forceCreate([
            'barcode' => 'S'.$number, 'follow_no' => $number, 'description' => 'Water', 'sampling_method' => 1,
            'date_registered' => (string) Carbon::now()->timestamp, 'registered_by' => 1, 'client' => $this->client->id,
            'subclient' => 0, 'project' => 1, 'custom_fields' => '{}',
            'predicted_end' => 0, 'sample_innoculated' => '', 'analyses_data' => '[]', ...$attributes,
        ]);
    }

    private function form(Carbon $date, bool $complete): AssuranceForm
    {
        return AssuranceForm::forceCreate(['date' => (string) $date->timestamp, 'data' => '{}', 'is_complete' => $complete ? 1 : 0]);
    }
}
