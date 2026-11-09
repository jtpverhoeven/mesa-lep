<?php

namespace Tests\Feature;

use App\Keyrings\KeyringType;
use App\Models\ChangeRevision;
use App\Models\Client;
use App\Models\EmailTemplate;
use App\Models\Export;
use App\Models\FileHistory;
use App\Models\Keyring;
use App\Models\Project;
use App\Models\Sample;
use App\Models\SampleFile;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use ZipArchive;

class ReportSenderTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => ':memory:',
            'services.glass.base_url' => 'http://portal.test/api',
        ]);
        DB::purge('sqlite');

        foreach (glob(database_path('migrations/*.php')) as $path) {
            (require $path)->up();
        }

        Storage::fake('local');
        Http::preventStrayRequests();
    }

    public function test_unknown_transaction_and_missing_pdf_show_the_legacy_error_messages(): void
    {
        $this->signIn();
        $project = $this->project();
        Export::factory()->inTransaction(5)->create(['project' => $project->id, 'client' => $project->client]);

        $this->get(route('reports.send.show', 99))->assertNotFound();
        $this->get(route('reports.send.show', 5))->assertNotFound();
        $this->get(route('reports.zip', 5))->assertNotFound();
        $this->get(route('reports.download', Export::query()->firstOrFail()))->assertNotFound();
    }

    public function test_page_config_lists_reports_languages_files_and_contact_groups(): void
    {
        $this->signIn();
        $this->fakePortal([['id' => 1, 'name' => 'Directie', 'members' => [['name' => 'Piet', 'email' => 'piet@example.test']]], ['id' => 2, 'name' => 'Kwaliteit', 'members' => []]]);
        EmailTemplate::factory()->create(['name' => 'Standaard', 'defaultmail' => 1, 'defaultmail_with_files' => 1, 'defaultmail_for_files' => 1, 'template' => 'Hallo', 'template_en' => 'Hello', 'bcc' => 'archief@example.test']);
        $project = $this->project();
        $scoped = $this->sample($project, 'S-1');
        $other = $this->sample($project, 'S-2');
        $attached = SampleFile::factory()->create(['sample_id' => $scoped->id, 'visible_for_client' => 1, 'original_name' => 'bijlage.pdf']);
        $orphan = SampleFile::factory()->create(['sample_id' => $other->id, 'visible_for_client' => 1, 'original_name' => 'los.pdf']);
        SampleFile::factory()->create(['sample_id' => $scoped->id, 'visible_for_client' => 0]);
        FileHistory::factory()->create(['sample_id' => $scoped->id, 'samplefile_id' => $attached->id, 'hash_name' => $attached->hash_name]);
        Export::factory()->inTransaction(7)->withPdf()->withSampleScope([$scoped->id])->create(['id' => 7, 'project' => $project->id, 'client' => $project->client, 'template' => 'maz2020En', 'revision' => 2, 'print_version' => 3]);
        Export::factory()->inTransaction(7)->withPdf()->withSampleScope([$scoped->id])->create(['id' => 8, 'project' => $project->id, 'client' => $project->client, 'revision' => 2, 'print_version' => 4]);

        $response = $this->get(route('reports.send.show', 7))->assertOk()->assertViewIs('reports.send');
        $config = $response->viewData('config');

        $this->assertSame('en', $config['defaultLanguage']);
        $this->assertSame('Hello', $config['templates']['reports']['template']);
        $this->assertSame('archief@example.test', $config['bcc']);
        $this->assertSame(['261024.2.3', '261024.2.4'], array_column($config['reports'], 'name'));
        $this->assertSame(route('reports.zip', 7), $config['zipUrl']);
        $this->assertSame(['bijlage.pdf'], array_column($config['files']['attachments'], 'original_name'));
        $this->assertSame(['Verstuurd, 1 keer'], array_column($config['files']['attachments'], 'sent_label'));
        $this->assertSame(['los.pdf'], array_column($config['files']['orphaned'], 'original_name'));
        $this->assertSame(['Directie', 'Kwaliteit'], array_column($config['contactGroups'], 'name'));
        $this->assertSame([false, false], array_column($config['contactGroups'], 'checked'));
        $this->assertNull($config['contactGroupsError']);
        $this->assertFalse($config['unauthorized']);
    }

    public function test_a_single_contact_group_is_preselected(): void
    {
        $this->signIn();
        $project = $this->project();
        Export::factory()->inTransaction(3)->withPdf()->create(['project' => $project->id, 'client' => $project->client]);

        $this->fakePortal([['id' => 9, 'name' => 'Enige groep', 'members' => []]]);
        $config = $this->get(route('reports.send.show', 3))->assertOk()->viewData('config');
        $this->assertSame([true], array_column($config['contactGroups'], 'checked'));
        $this->assertNull($config['zipUrl']);
    }

    public function test_portal_failures_do_not_break_the_sender_page(): void
    {
        $this->signIn();
        $project = $this->project();
        Export::factory()->inTransaction(3)->withPdf()->create(['project' => $project->id, 'client' => $project->client]);

        $this->fakePortal([], 500);
        $config = $this->get(route('reports.send.show', 3))->assertOk()->viewData('config');
        $this->assertSame([], $config['contactGroups']);
        $this->assertNotNull($config['contactGroupsError']);
    }

    public function test_unauthorized_reports_hide_the_sending_form_and_list_older_versions(): void
    {
        $this->signIn();
        $project = $this->project();
        Export::factory()->inTransaction(4)->withPdf()->create(['project' => $project->id, 'client' => $project->client]);
        Export::factory()->inTransaction(6)->withPdf()->unauthorized()->create(['project' => $project->id, 'client' => $project->client]);

        $config = $this->get(route('reports.send.show', 6))->assertOk()->viewData('config');

        $this->assertTrue($config['unauthorized']);
        $this->assertArrayNotHasKey('contactGroups', $config);
        $this->assertArrayNotHasKey('files', $config);
        $this->assertSame([6, 4], array_column($config['versions'], 'transaction'));
        Http::assertNothingSent();
    }

    public function test_the_export_lock_of_another_user_is_reported_to_the_page(): void
    {
        $this->signIn();
        $project = $this->project();
        Export::factory()->inTransaction(3)->withPdf()->create(['project' => $project->id, 'client' => $project->client]);
        Keyring::factory()->forResource(KeyringType::Export, $project->id)->heldBy(User::factory()->create(['enabled' => 1]))->create();
        $this->fakePortal([]);

        $config = $this->get(route('reports.send.show', 3))->assertOk()->viewData('config');

        $this->assertTrue($config['editability']['keyring']['locked']);
    }

    public function test_mixed_check_compares_the_selected_files_per_report(): void
    {
        $this->signIn();
        $project = $this->project();
        $first = $this->sample($project, 'S-1');
        $second = $this->sample($project, 'S-2');
        $fileOne = SampleFile::factory()->create(['sample_id' => $first->id, 'visible_for_client' => 1]);
        $fileTwo = SampleFile::factory()->create(['sample_id' => $second->id, 'visible_for_client' => 1]);
        Export::factory()->inTransaction(2)->withPdf()->withSampleScope([$first->id])->create(['id' => 2, 'project' => $project->id, 'client' => $project->client]);
        Export::factory()->inTransaction(2)->withPdf()->withSampleScope([$second->id])->create(['id' => 3, 'project' => $project->id, 'client' => $project->client]);
        $url = route('reports.send.mixed', 2);

        $this->postJson($url, ['selected_files' => [$fileOne->hash_name]])->assertOk()->assertContent('true');
        $this->postJson($url, ['selected_files' => [$fileOne->hash_name, $fileTwo->hash_name]])->assertOk()->assertContent('false');
        $this->postJson($url, ['selected_files' => []])->assertOk()->assertContent('false');
        $this->postJson($url)->assertOk()->assertContent('false');
        $this->postJson($url, ['selected_files' => 'nope'])->assertUnprocessable();
        $this->postJson(route('reports.send.mixed', 99), ['selected_files' => []])->assertNotFound();
    }

    public function test_reports_can_be_downloaded_individually_and_as_a_zip_with_unique_names(): void
    {
        $this->signIn();
        $project = $this->project();
        $first = Export::factory()->inTransaction(2)->withPdf('one')->create(['id' => 2, 'project' => $project->id, 'client' => $project->client, 'revision' => 2]);
        Export::factory()->inTransaction(2)->withPdf('two')->create(['id' => 3, 'project' => $project->id, 'client' => $project->client, 'revision' => 2]);

        $download = $this->get(route('reports.download', $first))->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringContainsString('Voorbeeld - MAZ-L 261024.2.1.pdf', $download->headers->get('Content-Disposition'));

        $zip = $this->get(route('reports.zip', 2))->assertOk();
        $this->assertStringContainsString('2 rapportages uit MAZ-L 261024.2.zip', $zip->headers->get('Content-Disposition'));

        $archive = new ZipArchive;
        $this->assertTrue($archive->open($zip->baseResponse->getFile()->getPathname()));
        $names = [$archive->getNameIndex(0), $archive->getNameIndex(1)];
        sort($names);
        $this->assertSame(['Voorbeeld - MAZ-L 261024.2.1(1).pdf', 'Voorbeeld - MAZ-L 261024.2.1.pdf'], $names);
        $archive->close();
    }

    public function test_file_history_endpoint_lists_deliveries_with_sender_and_report_links(): void
    {
        $this->signIn();
        $project = $this->project();
        $sample = $this->sample($project, 'S-1');
        $file = SampleFile::factory()->create(['sample_id' => $sample->id, 'visible_for_client' => 1]);
        $sender = User::factory()->create(['enabled' => 1, 'name' => 'Marie']);
        FileHistory::factory()->create(['samplefile_id' => $file->id, 'sample_id' => $sample->id, 'sent_by' => $sender->id, 'sent_method' => 'report', 'transaction_id' => 12, 'sent_to' => ['groups' => ['Directie'], 'extra' => 'x@example.test']]);
        FileHistory::factory()->create(['samplefile_id' => $file->id, 'sample_id' => $sample->id, 'sent_by' => $sender->id, 'sent_method' => 'filesender', 'sent_to' => ['groups' => [], 'extra' => '']]);

        $history = $this->getJson(route('sample-files.history', $file))->assertOk()->json('history');

        $this->assertCount(2, $history);
        $byMethod = array_column($history, null, 'method');
        $this->assertSame(route('reports.send.show', 12), $byMethod['Samen met rapport']['url']);
        $this->assertSame(['Directie'], $byMethod['Samen met rapport']['groups']);
        $this->assertSame('x@example.test', $byMethod['Samen met rapport']['extra']);
        $this->assertSame('Marie', $byMethod['Samen met rapport']['sent_by']);
        $this->assertNull($byMethod['Via bestands mailer']['url']);
    }

    public function test_template_endpoint_returns_the_language_specific_text_and_bcc(): void
    {
        $this->signIn();
        $template = EmailTemplate::factory()->create(['template' => 'Hallo', 'template_en' => 'Hello', 'bcc' => 'bcc@example.test']);

        $this->getJson(route('send.template', [$template, 'en']))->assertOk()->assertExactJson(['template' => 'Hello', 'bcc' => 'bcc@example.test']);
        $this->getJson(route('send.template', $template))->assertOk()->assertJson(['template' => 'Hallo']);
        $this->getJson('/laboratory/send/templates/'.$template->id.'/fr')->assertNotFound();
    }

    public function test_file_sender_lists_all_visible_files_and_redirects_for_unknown_projects(): void
    {
        $this->signIn();
        $this->fakePortal([['id' => 1, 'name' => 'Directie', 'members' => []]]);
        EmailTemplate::factory()->create(['defaultmail_for_files' => 1, 'template' => 'Bestanden', 'bcc' => 'b@example.test']);
        $project = $this->project();
        $sample = $this->sample($project, 'S-1');
        SampleFile::factory()->create(['sample_id' => $sample->id, 'visible_for_client' => 1, 'original_name' => 'een.pdf']);
        SampleFile::factory()->create(['sample_id' => $sample->id, 'visible_for_client' => 0]);

        $config = $this->get(route('projects.send-files.show', $project))->assertOk()->assertViewIs('reports.send-files')->viewData('config');

        $this->assertSame(['een.pdf'], array_column($config['files'], 'original_name'));
        $this->assertSame('Bestanden', $config['template']['template']);
        $this->assertSame('b@example.test', $config['bcc']);
        $this->assertSame([true], array_column($config['contactGroups'], 'checked'));
        $this->assertSame(route('projects.search', ['project' => $project->id]), $config['urls']['project']);

        $this->get(route('projects.send-files.show', 9999))->assertRedirect(route('projects.overview.completed'));
    }

    public function test_mailer_posts_reports_and_records_deliveries(): void
    {
        $user = $this->signIn();
        Http::fake([
            'http://portal.test/api/contact-lists/*' => Http::response([['id' => 1, 'name' => 'Directie'], ['id' => 2, 'name' => 'Kwaliteit']]),
            'http://portal.test/api/*' => Http::response(['message' => 'ok']),
        ]);
        $project = $this->project();
        $scoped = $this->sample($project, 'S-1');
        $other = $this->sample($project, 'S-2');
        $attached = SampleFile::factory()->create(['sample_id' => $scoped->id, 'visible_for_client' => 1, 'original_name' => 'bijlage.pdf']);
        $orphan = SampleFile::factory()->create(['sample_id' => $other->id, 'visible_for_client' => 1, 'original_name' => 'los.pdf']);
        Export::factory()->inTransaction(7)->withPdf()->withSampleScope([$scoped->id])->create(['id' => 7, 'project' => $project->id, 'client' => $project->client]);

        $this->postJson(route('reports.send.mail', 7), [
            'receivers' => ['1', '2', '99'], 'extraReceiver' => 'a@x.nl;b@y.nl', 'cc' => 'cc@x.nl', 'bcc' => 'bcc@x.nl',
            'message' => 'Zonder', 'messageAtt' => 'Met', 'messageFiles' => 'Los', 'exportAll' => true,
            'includeSampleFiles' => [$attached->hash_name, 'unknown-hash'], 'includeOrphanedSampleFiles' => [$orphan->hash_name],
        ])->assertOk()->assertExactJson(['status' => true, 'message' => '']);

        Http::assertSent(fn ($request) => str_ends_with($request->url(), '/mail-report') && $this->field($request, 'message') === 'Met'
            && $this->field($request, 'includeSampleFiles') === json_encode([$attached->hash_name]) && $this->field($request, 'emails') === '["1","2","99"]');
        Http::assertSent(fn ($request) => str_ends_with($request->url(), '/mail-document') && $this->field($request, 'message') === 'Los' && $this->field($request, 'sample') === (string) $other->id);
        Http::assertSent(fn ($request) => str_ends_with($request->url(), '/notify-reports'));

        $this->assertTrue($attached->refresh()->sent_to_client);
        $this->assertTrue($orphan->refresh()->sent_to_client);
        $report = FileHistory::query()->where('samplefile_id', $attached->id)->firstOrFail();
        $this->assertSame(['groups' => 'Directie;Kwaliteit;', 'extra' => 'a@x.nl;b@y.nl'], $report->sent_to);
        $this->assertSame('report', $report->sent_method);
        $this->assertSame(7, $report->transaction_id);
        $this->assertSame($user->id, $report->sent_by);
        $filesender = FileHistory::query()->where('samplefile_id', $orphan->id)->firstOrFail();
        $this->assertSame('filesender', $filesender->sent_method);
        $this->assertNull($filesender->transaction_id);

        $events = ChangeRevision::query()->where('type', '21')->orderBy('id')->pluck('event')->all();
        $this->assertSame([
            'Project verstuurd naar portal-mailer. Contact-groepen: Directie;Kwaliteit;, Extra ontvanger: a@x.nl;b@y.nl , CC: cc@x.nl , BCC: bcc@x.nl  ('.Export::query()->find(7)->report_reference.'.1.1). Bestanden verstuurd mee-verstuurd: bijlage.pdf',
            'Document verstuurd naar portal-document-mailer',
        ], $events);
    }

    public function test_portal_failure_is_reported_without_bookkeeping_but_still_notifies(): void
    {
        $this->signIn();
        Http::fake([
            'http://portal.test/api/mail-report' => Http::response(['message' => 'boom']),
            'http://portal.test/api/*' => Http::response([]),
        ]);
        $project = $this->project();
        $sample = $this->sample($project, 'S-1');
        $file = SampleFile::factory()->create(['sample_id' => $sample->id, 'visible_for_client' => 1]);
        Export::factory()->inTransaction(7)->withPdf()->withSampleScope([$sample->id])->create(['id' => 7, 'project' => $project->id, 'client' => $project->client]);

        $this->postJson(route('reports.send.mail', 7), ['exportAll' => true, 'includeSampleFiles' => [$file->hash_name]])
            ->assertOk()->assertExactJson(['status' => false, 'message' => ';261024']);

        $this->assertFalse($file->refresh()->sent_to_client);
        $this->assertSame(0, FileHistory::query()->count());
        $this->assertSame(0, ChangeRevision::query()->count());
        Http::assertSent(fn ($request) => str_ends_with($request->url(), '/notify-reports'));
    }

    public function test_a_server_error_from_the_portal_fails_the_report(): void
    {
        $this->signIn();
        Http::fake(['http://portal.test/api/mail-report' => Http::response('', 500), 'http://portal.test/api/*' => Http::response([])]);
        $project = $this->project();
        Export::factory()->inTransaction(7)->withPdf()->create(['id' => 7, 'project' => $project->id, 'client' => $project->client]);

        $this->postJson(route('reports.send.mail', 7), ['exportAll' => true])->assertOk()->assertJson(['status' => false, 'message' => ';261024']);
    }

    public function test_unauthorised_reports_are_refused_without_calling_the_portal(): void
    {
        $this->signIn();
        $project = $this->project();
        Export::factory()->inTransaction(7)->withPdf()->create(['id' => 7, 'project' => $project->id, 'client' => $project->client, 'was_authorized' => 0]);

        $this->postJson(route('reports.send.mail', 7), ['exportAll' => true])->assertStatus(422)->assertJson(['status' => false]);
        Http::assertNothingSent();
        $this->postJson(route('reports.send.mail', 99), ['exportAll' => true])->assertNotFound();
    }

    public function test_only_selected_reports_are_sent_when_not_exporting_all(): void
    {
        $this->signIn();
        Http::fake(['http://portal.test/api/*' => Http::response(['message' => 'ok'])]);
        $project = $this->project();
        Export::factory()->inTransaction(7)->withPdf()->create(['id' => 7, 'project' => $project->id, 'client' => $project->client]);
        Export::factory()->inTransaction(7)->withPdf()->create(['id' => 8, 'project' => $project->id, 'client' => $project->client, 'revision' => 2]);

        $this->postJson(route('reports.send.mail', 7), ['exportAll' => false, 'selectedReports' => [8]])->assertOk()->assertJson(['status' => true]);

        Http::assertSentCount(2);
        Http::assertSent(fn ($request) => str_ends_with($request->url(), '/mail-report') && $this->field($request, 'revision') === '2');
    }

    public function test_file_sender_groups_files_by_sample_and_records_filesender_history(): void
    {
        $this->signIn();
        Http::fake([
            'http://portal.test/api/contact-lists/*' => Http::response([['id' => 1, 'name' => 'Directie']]),
            'http://portal.test/api/mail-document' => Http::response(['message' => 'whatever']),
        ]);
        $project = $this->project();
        $one = $this->sample($project, 'S-1');
        $two = $this->sample($project, 'S-2');
        $a = SampleFile::factory()->create(['sample_id' => $one->id, 'visible_for_client' => 1]);
        $b = SampleFile::factory()->create(['sample_id' => $two->id, 'visible_for_client' => 1]);
        $hidden = SampleFile::factory()->create(['sample_id' => $one->id, 'visible_for_client' => 0]);

        $this->postJson(route('projects.send-files.mail', $project), [
            'receivers' => ['1'], 'extraReceiver' => 'x@y.nl', 'messageFiles' => 'Bestanden',
            'includeSampleFiles' => [$a->hash_name, $b->hash_name, $hidden->hash_name],
        ])->assertOk()->assertExactJson(['message' => 'ok']);

        Http::assertSentCount(3);
        $this->assertSame(2, FileHistory::query()->where('sent_method', 'filesender')->whereNull('transaction_id')->count());
        $this->assertFalse($hidden->refresh()->sent_to_client);
        $this->assertSame(['groups' => 'Directie', 'extra' => 'x@y.nl'], FileHistory::query()->firstOrFail()->sent_to);
        $this->assertSame(2, ChangeRevision::query()->where('type', '21')->count());
    }

    public function test_file_sender_answers_empty_and_ignores_the_portal_response_like_legacy(): void
    {
        $this->signIn();
        Http::fake(['http://portal.test/api/contact-lists/*' => Http::response([]), 'http://portal.test/api/mail-document' => Http::response('', 503)]);
        $project = $this->project();
        $sample = $this->sample($project, 'S-1');
        $file = SampleFile::factory()->create(['sample_id' => $sample->id, 'visible_for_client' => 1]);

        $this->postJson(route('projects.send-files.mail', $project), [])->assertOk()->assertExactJson(['message' => 'empty']);
        $this->postJson(route('projects.send-files.mail', $project), ['includeSampleFiles' => [$file->hash_name]])->assertOk()->assertExactJson(['message' => 'ok']);

        $this->assertTrue($file->refresh()->sent_to_client);
        $this->assertSame(1, FileHistory::query()->count());
    }

    public function test_sending_pages_require_report_permission(): void
    {
        $this->actingAs(User::factory()->create(['enabled' => 1]));
        Gate::define('projects.view', fn (): bool => true);
        $project = $this->project();

        $this->get(route('reports.send.show', 1))->assertForbidden();
        $this->get(route('projects.send-files.show', $project))->assertForbidden();
        $this->postJson(route('reports.send.mixed', 1))->assertForbidden();
        $this->getJson(route('reports.zip', 1))->assertForbidden();
    }

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get(route('reports.send.show', 1))->assertRedirect(route('login'));
    }

    private function signIn(): User
    {
        $user = User::factory()->create(['enabled' => 1]);
        $this->actingAs($user);
        Gate::define('projects.view', fn (): bool => true);
        Gate::define('reports.generate', fn (): bool => true);

        return $user;
    }

    private function project(): Project
    {
        $client = Client::query()->create(['name' => 'Voorbeeld', 'title' => 'Dhr.', 'fname' => 'Jan', 'lname' => 'Jansen']);

        return Project::query()->create(['client' => $client->id, 'subclient' => 0, 'reference' => '261024', 'revision' => 2]);
    }

    private function sample(Project $project, string $barcode): Sample
    {
        return Sample::query()->create([
            'barcode' => $barcode, 'follow_no' => 0, 'description' => '', 'sampling_method' => 1,
            'date_registered' => '2026-11-08', 'registered_by' => 1, 'client' => 1, 'subclient' => 0,
            'project' => $project->id, 'custom_fields' => '{}', 'predicted_end' => 0,
            'sample_innoculated' => '2026-11-08', 'analyses_data' => '{}',
        ]);
    }

    /** @param list<array<string, mixed>> $groups */
    private function fakePortal(array $groups, int $status = 200): void
    {
        Http::fake(['http://portal.test/api/contact-lists/*' => Http::response($groups, $status)]);
    }

    private function field(mixed $request, string $name): ?string
    {
        foreach ($request->data() as $part) {
            if ($part['name'] === $name) {
                return $part['contents'];
            }
        }

        return null;
    }
}
