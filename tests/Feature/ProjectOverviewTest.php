<?php

namespace Tests\Feature;

use App\Actions\Projects\GetProjectOverview;
use App\Models\Profile;
use App\Models\Project;
use App\Models\Sample;
use App\Models\SampleAnalysis;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ProjectOverviewTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');
        foreach (glob(database_path('migrations/*.php')) as $path) {
            (require $path)->up();
        }
    }

    #[DataProvider('legacyMemberships')]
    public function test_overviews_preserve_legacy_membership_including_overlaps(string $status, array $expected): void
    {
        $this->allowOverview();
        $states = [
            ['started' => 0],
            ['started' => 0, 'locked' => 1],
            ['started' => 0, 'is_ready' => 1, 'auth_status' => 1, 'rap_stat' => 1],
            ['started' => 1],
            ['started' => 1, 'locked' => 1],
            ['started' => 1, 'is_ready' => 1],
            ['started' => 1, 'is_ready' => 1, 'locked' => 1],
            ['started' => 1, 'auth_status' => 1],
            ['started' => 1, 'auth_status' => 1, 'rap_stat' => 1],
            ['started' => 1, 'auth_status' => 1, 'locked' => 1],
        ];
        foreach ($states as $index => $state) {
            Project::forceCreate([
                'id' => $index + 1, 'client' => 1, 'subclient' => 0, 'project_name' => 'Legacy project',
                'started' => 0, 'is_ready' => 0, 'auth_status' => 0, 'rap_stat' => 0, 'locked' => 0,
                ...$state,
            ]);
        }

        $response = $this->getJson(route('projects.overview.'.$status.'.data', ['status' => 'invalid']));

        $response->assertOk();
        $this->assertSame($expected, array_column($response->json('data'), 'id'));
    }

    public static function legacyMemberships(): array
    {
        return [
            'received includes unstarted authorized historical records' => ['received', [1, 2, 3]],
            'running includes blocked projects' => ['running', [4, 5]],
            'completed includes blocked projects' => ['completed', [6, 7]],
            'authorized requires started but not ready or unlocked' => ['authorized', [8, 10]],
            'reported requires authorization and started' => ['reported', [9]],
            'blocked includes unstarted running and completed but not authorized' => ['blocked', [2, 5, 7]],
        ];
    }

    #[DataProvider('legacyMemberships')]
    public function test_each_status_has_a_separate_page_and_data_endpoint(string $status): void
    {
        $this->allowOverview();

        $this->get(route('projects.overview.'.$status, ['status' => 'invalid']))
            ->assertOk()->assertViewIs('projects.overview')->assertViewHas('status', $status)
            ->assertSee('project-overview');
        $this->getJson(route('projects.overview.'.$status.'.data'))
            ->assertOk()->assertJsonPath('per_page', 50)->assertJsonPath('total', 0);
    }

    #[DataProvider('legacyMemberships')]
    public function test_each_overview_returns_403_without_project_view_permission(string $status): void
    {
        $this->actingAs(User::factory()->create(['enabled' => 1]));
        Gate::define('projects.view', fn (): bool => false);

        $this->get(route('projects.overview.'.$status))->assertForbidden();
        $this->getJson(route('projects.overview.'.$status.'.data'))->assertForbidden();
    }

    #[DataProvider('legacyMemberships')]
    public function test_each_overview_redirects_guests_to_login(string $status): void
    {
        $this->get(route('projects.overview.'.$status))->assertRedirect(route('login'));
        $this->getJson(route('projects.overview.'.$status.'.data'))->assertUnauthorized();
    }

    public function test_label_assignment_requires_samples_and_analyses_from_the_same_project(): void
    {
        $this->allowOverview();
        $empty = $this->project();
        $assigned = $this->project();
        $partial = $this->project();
        $mismatched = $this->project();
        $assignedSample = $this->sample($assigned);
        $partialSample = $this->sample($partial);
        $this->sample($partial);
        $mismatchedSample = $this->sample($mismatched);
        $analysisAttributes = [
            'profile_group' => 1, 'follow_number' => 1, 'profile' => 0,
            'assay' => 1, 'assay_base' => 1, 'predicted_end' => -1,
        ];
        foreach ([$assignedSample, $partialSample] as $sample) {
            SampleAnalysis::create([...$analysisAttributes, 'sample' => $sample->id, 'project' => $sample->project]);
        }
        SampleAnalysis::create([...$analysisAttributes, 'sample' => $mismatchedSample->id, 'project' => $assigned->id]);

        $response = $this->getJson(route('projects.overview.received.data'));

        $response->assertOk()
            ->assertJsonPath('data.0.id', $empty->id)->assertJsonPath('data.0.all_samples_have_analysis', false)
            ->assertJsonPath('data.1.id', $assigned->id)->assertJsonPath('data.1.all_samples_have_analysis', true)
            ->assertJsonPath('data.2.id', $partial->id)->assertJsonPath('data.2.all_samples_have_analysis', false)
            ->assertJsonPath('data.3.id', $mismatched->id)->assertJsonPath('data.3.all_samples_have_analysis', false);
    }

    public function test_invalid_page_returns_422(): void
    {
        $this->allowOverview();

        $this->getJson(route('projects.overview.received.data', ['page' => 0]))
            ->assertUnprocessable()->assertJsonValidationErrors('page');
    }

    #[DataProvider('legacySortOrders')]
    public function test_overviews_keep_legacy_sort_order_with_a_stable_tie_breaker(string $status, array $state, string $sort, array $expected): void
    {
        $this->allowOverview();
        foreach ([300, 100, 100] as $value) {
            $this->project([...$state, $sort => $value]);
        }

        $response = $this->getJson(route('projects.overview.'.$status.'.data'));

        $response->assertOk();
        $this->assertSame($expected, array_column($response->json('data'), 'id'));
    }

    public static function legacySortOrders(): array
    {
        return [
            'received oldest registration first' => ['received', [], 'project_date', [2, 3, 1]],
            'running earliest predicted end first' => ['running', ['started' => 1], 'predicted_end', [2, 3, 1]],
            'completed oldest ready date first' => ['completed', ['started' => 1, 'is_ready' => 1], 'became_ready_on', [2, 3, 1]],
            'authorized oldest authorization first' => ['authorized', ['started' => 1, 'auth_status' => 1], 'auth_on', [2, 3, 1]],
            'reported latest PDF first' => ['reported', ['started' => 1, 'auth_status' => 1, 'rap_stat' => 1], 'rap_on', [1, 2, 3]],
            'blocked earliest predicted end first' => ['blocked', ['locked' => 1], 'predicted_end', [2, 3, 1]],
        ];
    }

    public function test_overviews_paginate_at_50_projects_without_skipping_or_duplicating_rows(): void
    {
        $this->allowOverview();
        for ($index = 1; $index <= 52; $index++) {
            $this->project(['project_date' => '1700000000']);
        }

        $first = $this->getJson(route('projects.overview.received.data'));
        $second = $this->getJson(route('projects.overview.received.data', ['page' => 2]));

        $first->assertOk()->assertJsonCount(50, 'data')->assertJsonPath('total', 52)->assertJsonPath('last_page', 2);
        $second->assertOk()->assertJsonCount(2, 'data')->assertJsonPath('current_page', 2);
        $this->assertSame(range(1, 50), array_column($first->json('data'), 'id'));
        $this->assertSame([51, 52], array_column($second->json('data'), 'id'));
    }

    public function test_report_metadata_preserves_legacy_json_and_uses_the_reporters_profile(): void
    {
        $this->allowOverview();
        $reporter = User::factory()->create(['name' => 'Account name']);
        Profile::create(['user_id' => $reporter->id, 'username' => 'reporter', 'first_name' => 'Lab', 'last_name' => 'Analist']);
        $project = $this->project([
            'started' => 1, 'auth_status' => 1, 'rap_stat' => 1, 'rap_by' => $reporter->id,
            'reference' => 'MAZ-123', 'project_name' => ' MAZ-123 ', 'rap_on' => '1700000000',
            'custom_fields' => json_encode(['project_ontvangst' => '07-10-2026']),
            'print_info' => json_encode(['last_print_name' => 'Administrator', 'print_times' => 3]),
            'lock_pass' => 'not-for-the-overview',
        ]);

        $response = $this->getJson(route('projects.overview.reported.data'));

        $response->assertOk()->assertJsonPath('data.0.id', $project->id)
            ->assertJsonPath('data.0.reported_by', 'Lab Analist')
            ->assertJsonPath('data.0.received_date', '07-10-2026')
            ->assertJsonPath('data.0.client_reference', '')
            ->assertJsonPath('data.0.last_print_name', 'Administrator')
            ->assertJsonPath('data.0.print_times', 3)
            ->assertJsonPath('data.0.rap_on', '1700000000');
        $this->assertArrayNotHasKey('lock_pass', $response->json('data.0'));
    }

    public function test_missing_relations_and_malformed_legacy_json_do_not_break_an_overview(): void
    {
        $this->allowOverview();
        $this->project(['custom_fields' => 'broken json', 'print_info' => '123']);

        $this->getJson(route('projects.overview.received.data'))->assertOk()
            ->assertJsonPath('data.0.client_name', '?')->assertJsonPath('data.0.reported_by', '?')
            ->assertJsonPath('data.0.received_date', null)->assertJsonPath('data.0.last_print_name', '')
            ->assertJsonPath('data.0.print_times', null);
    }

    #[DataProvider('legacyProgressValues')]
    public function test_progress_remains_time_based_not_analysis_completion_based(?int $end, int $start, int $authorized, int $expected): void
    {
        $this->travelTo(Carbon::createFromTimestamp(1700000100));
        $project = $this->project(['predicted_end' => $end, 'project_date' => (string) $start, 'auth_status' => $authorized]);

        $result = app(GetProjectOverview::class)->handle('received')->items()[0];

        $this->assertSame($project->id, $result['id']);
        $this->assertSame($expected, $result['progress']);
    }

    public static function legacyProgressValues(): array
    {
        return [
            'halfway' => [1700000200, 1700000000, 0, 50],
            'overdue capped at 100' => [1700000050, 1700000000, 0, 100],
            'unknown sentinel' => [-1, 1700000000, 0, 0],
            'missing legacy deadline' => [null, 1700000000, 0, 0],
            'zero duration' => [1700000000, 1700000000, 0, 100],
            'authorized overrides unknown deadline' => [-1, 1700000000, 1, 100],
        ];
    }

    #[DataProvider('legacyDeadlineWarnings')]
    public function test_overdue_warnings_keep_legacy_weekend_holiday_and_three_workday_rules(string $today, ?string $end, ?string $expected): void
    {
        $this->travelTo(Carbon::parse($today, 'Europe/Amsterdam'));
        $this->project([
            'started' => 1,
            'predicted_end' => $end === null ? -1 : Carbon::parse($end, 'Europe/Amsterdam')->timestamp,
        ]);

        $result = app(GetProjectOverview::class)->handle('running')->items()[0];

        $this->assertSame($expected, $result['deadline_tone']);
    }

    public static function legacyDeadlineWarnings(): array
    {
        return [
            'unknown' => ['2026-04-30', null, null],
            'due today' => ['2026-04-30', '2026-04-30', null],
            'future' => ['2026-04-30', '2026-05-01', null],
            'weekend still warns with zero elapsed workdays' => ['2026-04-27', '2026-04-25', 'warning'],
            'Kings Day and weekend excluded at three workdays' => ['2026-04-30', '2026-04-24', 'warning'],
            'four workdays' => ['2026-04-30', '2026-04-23', 'danger'],
            'Easter Monday excluded' => ['2026-04-10', '2026-04-04', 'warning'],
            'Liberation Day excluded every five years' => ['2025-05-09', '2025-05-05', 'warning'],
            'Liberation Day is otherwise a workday' => ['2026-05-09', '2026-05-05', 'danger'],
        ];
    }

    private function allowOverview(): void
    {
        $this->actingAs(User::factory()->create(['enabled' => 1]));
        Gate::define('projects.view', fn (): bool => true);
    }

    /** @param array<string, mixed> $attributes */
    private function project(array $attributes = []): Project
    {
        return Project::create(['client' => 1, 'subclient' => 0, ...$attributes]);
    }

    private function sample(Project $project): Sample
    {
        return Sample::create([
            'project' => $project->id, 'client' => 1, 'subclient' => 0,
            'barcode' => 'S'.(Sample::query()->count() + 1), 'follow_no' => 1, 'description' => 'Water',
            'sampling_method' => 0, 'date_registered' => '0', 'registered_by' => 1,
            'custom_fields' => '{}', 'predicted_end' => -1, 'sample_innoculated' => '-',
            'analyses_data' => '{}',
        ]);
    }
}
