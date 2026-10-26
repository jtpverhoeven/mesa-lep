<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Project;
use App\Models\ProjectField;
use App\Models\Sample;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ProjectInfoTest extends TestCase
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
        Gate::define('projects.view', fn () => true);
        Gate::define('samples.view', fn () => true);
    }

    #[DataProvider('statuses')]
    public function test_project_details_use_the_legacy_status_precedence(array $attributes, string $status): void
    {
        $project = $this->project($attributes);

        $this->getJson(route('projects.search.show', $project))
            ->assertOk()
            ->assertJsonPath('data.project_info.project.status', $status);
    }

    public static function statuses(): array
    {
        return [
            'received before work begins' => [[], 'Ontvangen'],
            'running' => [['started' => 1], 'Lopend'],
            'completed' => [['started' => 1, 'is_ready' => 1], 'Afgerond'],
            'received takes precedence over readiness' => [['is_ready' => 1], 'Ontvangen'],
            'authorized' => [['auth_status' => 1, 'started' => 1], 'Geautoriseerd'],
            'reported' => [['auth_status' => 1, 'rap_stat' => 1, 'locked' => 1], 'PDF gegenereerd'],
            'blocked before being started' => [['locked' => 1], 'Geblokkeerd'],
            'blocked during work' => [['started' => 1, 'locked' => 1], 'Geblokkeerd'],
        ];
    }

    public function test_both_pages_receive_the_same_project_details_and_ordered_fields(): void
    {
        ProjectField::create(['name' => 'project_monster', 'alias' => 'Bemonsterdatum', 'type' => 'text', 'std_value' => '', 'position' => 1]);
        ProjectField::create(['name' => 'project_bemonster_tijd', 'alias' => 'Bemonstertijd', 'type' => 'text', 'std_value' => '', 'position' => 2]);
        $project = $this->project([
            'project_name' => 'Klantproject', 'reference' => 'MAZ-42',
            'print_version' => 0, 'predicted_end' => CarbonImmutable::parse('2026-10-15 10:00', 'Europe/Amsterdam')->timestamp,
            'project_date' => (string) CarbonImmutable::parse('2026-10-10 09:00', 'Europe/Amsterdam')->timestamp,
            'custom_fields' => '{"project_monster":"10-10-2026","project_bemonster_tijd":"0900"}',
            'project_extra' => '{"client_reference":"Referentie van klant"}',
        ]);
        $sample = $this->sample($project, '26101001', '2026-10-10 12:00');
        $this->sample($project, '26101002', '2026-10-10 16:00');

        $details = $this->getJson(route('projects.search.show', $project))
            ->assertOk()
            ->assertJsonPath('data.project_info.project.client_name', 'Water BV')
            ->assertJsonPath('data.project_info.project.reference', 'MAZ-42')
            ->assertJsonPath('data.project_info.inoculation_date', '10-10-2026')
            ->assertJsonPath('data.project_info.inoculation_time', '12:00')
            ->assertJsonPath('data.project_info.sampling_to_analysis', '3 uur')
            ->assertJsonPath('data.project_info.expected_date', '15-10-2026')
            ->assertJsonPath('data.project_info.extra_fields.0.value', 'Referentie van klant')
            ->assertJsonPath('data.fields.0.label', 'Bemonsterdatum')
            ->assertJsonPath('data.fields.1.label', 'Bemonstertijd')
            ->assertJsonPath('data.project_info.portal_status_url', route('projects.info.portal-status', $project))
            ->json('data.project_info');

        $this->getJson(route('samples.lookup.data', ['barcode' => $sample->barcode]))
            ->assertOk()
            ->assertJsonPath('data.project_info', $details)
            ->assertJsonPath('data.project_samples.1.barcode', '26101002')
            ->assertJsonPath('data.project_url', route('projects.search', $project));
    }

    public function test_inoculation_spanning_days_shows_both_dates_and_uses_the_earliest_for_the_term(): void
    {
        $project = $this->project([
            'custom_fields' => '{"project_monster":"2026-10-10","project_bemonster_tijd":"0900"}',
        ]);
        $this->sample($project, '26101001', '2026-10-11 11:00');
        $this->sample($project, '26101002', '2026-10-10 12:00');

        $this->getJson(route('projects.search.show', $project))
            ->assertOk()
            ->assertJsonPath('data.project_info.inoculation_date', "10-10-2026 12:00\n11-10-2026 11:00")
            ->assertJsonPath('data.project_info.inoculation_time', 'Tweeledig')
            ->assertJsonPath('data.project_info.sampling_to_analysis', '3 uur');
    }

    public function test_colon_separated_sampling_time_keeps_the_legacy_zero_minutes_calculation(): void
    {
        $project = $this->project([
            'custom_fields' => '{"project_monster":"10-10-2026","project_bemonster_tijd":"09:30"}',
        ]);
        $this->sample($project, '26101001', '2026-10-10 12:00');

        $this->getJson(route('projects.search.show', $project))
            ->assertOk()
            ->assertJsonPath('data.project_info.sampling_to_analysis', '3 uur');
    }

    #[DataProvider('missingTermValues')]
    public function test_term_explains_missing_or_invalid_sampling_data(array $fields, bool $hasInoculation, string $expected): void
    {
        $project = $this->project(['custom_fields' => json_encode($fields)]);
        if ($hasInoculation) {
            $this->sample($project, '26101001', '2026-10-10 12:00');
        }

        $response = $this->getJson(route('projects.search.show', $project));

        $response
            ->assertOk()
            ->assertJsonPath('data.project_info.sampling_to_analysis', $expected)
            ->assertJsonPath('data.project_info.expected_date', 'Nog niet bekend');
        if (! $hasInoculation) {
            $response
                ->assertJsonPath('data.project_info.inoculation_date', 'Nog niet ingezet')
                ->assertJsonPath('data.project_info.inoculation_time', '');
        }
    }

    public static function missingTermValues(): array
    {
        return [
            'no inoculation' => [['project_monster' => '10-10-2026', 'project_bemonster_tijd' => '0900'], false, 'N.T.B. Geen inzet datum'],
            'unknown date' => [['project_monster' => 'Onbekend', 'project_bemonster_tijd' => '0900'], true, 'N.T.B. Bemonster-datum onbekend'],
            'missing time' => [['project_monster' => '10-10-2026'], true, 'N.T.B. Bemonster-tijd onbekend'],
            'invalid date' => [['project_monster' => '32-10-2026', 'project_bemonster_tijd' => '0900'], true, 'N.T.B. Datum/tijd veld(en) incorrect'],
            'invalid time' => [['project_monster' => '10-10-2026', 'project_bemonster_tijd' => '2500'], true, 'N.T.B. Datum/tijd veld(en) incorrect'],
        ];
    }

    public function test_authorization_exposes_the_author_name_date_avatar_and_reporter_without_private_fields(): void
    {
        Storage::fake('local');
        $author = User::factory()->create(['name' => 'Fallback Name']);
        $author->profile()->create(['first_name' => 'Ada', 'last_name' => 'Lovelace', 'avatar_path' => 'avatars/ada.png']);
        Storage::disk('local')->put('avatars/ada.png', 'avatar bytes');
        $project = $this->project([
            'auth_status' => 1, 'auth_by' => $author->id,
            'auth_on' => CarbonImmutable::parse('2026-10-12 13:00', 'Europe/Amsterdam')->timestamp,
            'rap_stat' => 1, 'rap_by' => $author->id, 'rap_on' => (string) CarbonImmutable::parse('2026-10-13 10:00', 'Europe/Amsterdam')->timestamp,
        ]);

        $this->getJson(route('projects.search.show', $project))
            ->assertOk()
            ->assertJsonPath('data.project_info.project.authorized_by.name', 'Ada Lovelace')
            ->assertJsonPath('data.project_info.project.authorized_by.avatar_url', route('projects.info.avatar', $project))
            ->assertJsonPath('data.project_info.project.auth_on', $project->auth_on)
            ->assertJsonPath('data.project_info.project.reported_by', 'Ada Lovelace')
            ->assertJsonMissingPath('data.project.lock_pass');
        $this->get(route('projects.info.avatar', $project))
            ->assertOk()
            ->assertHeader('Content-Type', 'image/png')
            ->assertStreamedContent('avatar bytes');

        $project->update(['auth_status' => 0]);
        $this->get(route('projects.info.avatar', $project))->assertNotFound();
    }

    #[DataProvider('portalCounts')]
    public function test_portal_status_checks_the_projects_client_through_the_portal(int $users, bool $active): void
    {
        $project = $this->project();
        config(['services.glass.base_url' => 'http://portal.test/api']);
        Http::preventStrayRequests();
        Http::fake(['http://portal.test/api/client-user-of-portal' => Http::response(['users' => $users])]);

        $this->getJson(route('projects.info.portal-status', $project))
            ->assertOk()
            ->assertJsonPath('active', $active);
        Http::assertSent(fn ($request): bool => $request->url() === 'http://portal.test/api/client-user-of-portal'
            && $request->hasFile('client', $project->client));
    }

    public static function portalCounts(): array
    {
        return ['active' => [2, true], 'inactive' => [0, false]];
    }

    public function test_portal_errors_are_not_reported_as_inactive_clients(): void
    {
        $project = $this->project();
        config(['services.glass.base_url' => 'http://portal.test/api']);
        Http::preventStrayRequests();
        Http::fake(['http://portal.test/api/client-user-of-portal' => Http::response(['message' => 'unavailable'], 503)]);

        $this->getJson(route('projects.info.portal-status', $project))
            ->assertStatus(502)
            ->assertJsonPath('message', 'Portaalstatus kon niet worden opgehaald.');
    }

    public function test_sample_viewers_can_check_portal_status_without_project_view_permission(): void
    {
        $project = $this->project();
        $sample = $this->sample($project, '26101001', '2026-10-10 12:00');
        Gate::define('projects.view', fn () => false);
        config(['services.glass.base_url' => 'http://portal.test/api']);
        Http::preventStrayRequests();
        Http::fake(['http://portal.test/api/client-user-of-portal' => Http::response(['users' => 0])]);

        $this->getJson(route('samples.lookup.data', ['barcode' => $sample->barcode]))
            ->assertOk()
            ->assertJsonPath('data.project_info.portal_status_url', route('projects.info.portal-status', $project));
        $this->getJson(route('projects.info.portal-status', $project))
            ->assertOk()
            ->assertJsonPath('active', false);
        Http::assertSentCount(1);
    }

    public function test_portal_and_avatar_endpoints_require_a_project_or_sample_view_permission(): void
    {
        $project = $this->project();
        Gate::define('projects.view', fn () => false);
        Gate::define('samples.view', fn () => false);
        Http::preventStrayRequests();

        $this->getJson(route('projects.info.portal-status', $project))->assertForbidden();
        $this->get(route('projects.info.avatar', $project))->assertForbidden();
        $this->get(route('projects.info.order-form', $project))->assertForbidden();
        Http::assertNothingSent();
    }

    public function test_order_form_download_uses_the_bound_projects_portal_id(): void
    {
        $project = $this->project(['portal_id' => 73]);
        config(['services.glass.base_url' => 'http://portal.test/api']);
        Http::preventStrayRequests();
        Http::fake(['http://portal.test/api/project-order-form' => Http::response(['status' => '200', 'pdf' => base64_encode('%PDF-1.4 example')])]);

        $this->getJson(route('projects.search.show', $project))
            ->assertJsonPath('data.project_info.order_form_url', route('projects.info.order-form', $project));
        $this->get(route('projects.info.order-form', $project))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf')
            ->assertContent('%PDF-1.4 example');
        Http::assertSent(fn ($request): bool => $request->url() === 'http://portal.test/api/project-order-form'
            && $request->hasFile('portal_project_id', 73));
    }

    public function test_order_form_is_unavailable_without_a_portal_project_or_a_saved_pdf(): void
    {
        $project = $this->project();
        $this->getJson(route('projects.search.show', $project))->assertJsonPath('data.project_info.order_form_url', null);
        $this->get(route('projects.info.order-form', $project))->assertNotFound();

        $project->update(['portal_id' => 73]);
        config(['services.glass.base_url' => 'http://portal.test/api']);
        Http::preventStrayRequests();
        Http::fake(['http://portal.test/api/project-order-form' => Http::response(['status' => '404'])]);

        $this->get(route('projects.info.order-form', $project))->assertNotFound();
        Http::assertSentCount(1);
    }

    private function project(array $attributes = []): Project
    {
        $client = Client::create(['name' => 'Water BV']);

        return Project::create([
            'client' => $client->id, 'subclient' => 0, 'project_name' => 'Project water',
            ...$attributes,
        ]);
    }

    private function sample(Project $project, string $barcode, string $inoculated): Sample
    {
        return Sample::create([
            'barcode' => $barcode, 'follow_no' => 1, 'description' => 'Water sample',
            'sampling_method' => 1, 'date_registered' => '1791590400', 'registered_by' => 1,
            'client' => $project->client, 'subclient' => 0, 'project' => $project->id,
            'custom_fields' => '{}', 'predicted_end' => -1,
            'sample_innoculated' => (string) CarbonImmutable::parse($inoculated, 'Europe/Amsterdam')->timestamp,
            'analyses_data' => '[]', 'sample_type' => 'S', 'sample_extra' => '{}',
        ]);
    }
}
