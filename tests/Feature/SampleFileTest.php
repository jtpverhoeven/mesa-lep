<?php

namespace Tests\Feature;

use App\Actions\SampleFiles\RecordSampleFileDelivery;
use App\ClientPortal\PortalSettings;
use App\Keyrings\KeyringType;
use App\Models\FileHistory;
use App\Models\Keyring;
use App\Models\Project;
use App\Models\Sample;
use App\Models\SampleFile;
use App\Models\User;
use Illuminate\Http\Client\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SampleFileTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => ':memory:',
        ]);
        DB::purge('sqlite');

        foreach (glob(database_path('migrations/*.php')) as $path) {
            (require $path)->up();
        }

        $this->actingAs(User::factory()->create(['enabled' => 1]));

        foreach (['samples.view', 'samples.update', 'projects.view'] as $permission) {
            Gate::define($permission, fn (): bool => true);
        }

        Storage::fake('local');
        Http::preventStrayRequests();
    }

    public function test_upload_stores_a_private_file_and_exposes_it_on_both_sample_pages(): void
    {
        [$project, $sample] = $this->sample();

        $this->post(route('samples.files.store', $sample), [
            'file' => UploadedFile::fake()->createWithContent('certificate.pdf', 'pdf bytes'),
        ])->assertCreated()
            ->assertJsonPath('data.0.original_name', 'certificate.pdf')
            ->assertJsonPath('data.0.visible_for_client', false)
            ->assertJsonMissingPath('data.0.hash_name');

        $sampleFile = SampleFile::query()->firstOrFail();
        Storage::disk('local')->assertExists($sampleFile->storagePath());
        $this->assertSame('pdf bytes', Storage::disk('local')->get($sampleFile->storagePath()));
        $this->assertDatabaseHas('samplefiles', [
            'sample_id' => $sample->id,
            'client_id' => $sample->client,
            'original_name' => 'certificate.pdf',
            'visible_for_client' => 0,
            'sent_to_client' => 0,
        ]);
        $this->assertDatabaseHas('changetracker', [
            'type' => '25',
            'project' => $project->id,
            'sample' => $sample->id,
            'event' => 'Bestand geupload',
            'from' => '',
            'to' => 'certificate.pdf',
        ]);

        $this->getJson(route('samples.lookup.data', ['barcode' => $sample->barcode]))
            ->assertOk()
            ->assertJsonPath('data.sample_files.0.id', $sampleFile->id)
            ->assertJsonPath('data.sample_files.0.download_url', route('samples.files.download', [$sample, $sampleFile]));
        $this->getJson(route('projects.search.samples.show', [$project, $sample]))
            ->assertOk()
            ->assertJsonPath('data.sample_files.0.id', $sampleFile->id);
    }

    public function test_photo_mode_uses_the_first_available_legacy_barcode_filename(): void
    {
        [, $sample] = $this->sample();
        SampleFile::query()->create([
            'sample_id' => $sample->id,
            'client_id' => $sample->client,
            'hash_name' => 'existing-hash',
            'original_name' => $sample->barcode.'_1.jpg',
            'visible_for_client' => false,
            'sent_to_client' => false,
        ]);

        $this->post(route('samples.files.store', $sample), [
            'file' => UploadedFile::fake()->createWithContent('camera.jpeg', 'image bytes'),
            'photoMode' => '1',
        ])->assertCreated()
            ->assertJsonPath('data.1.original_name', $sample->barcode.'_2.jpeg');

        $this->assertDatabaseHas('samplefiles', [
            'sample_id' => $sample->id,
            'original_name' => $sample->barcode.'_2.jpeg',
        ]);
    }

    public function test_legacy_instant_visibility_input_uploads_the_new_file_to_the_portal(): void
    {
        [, $sample] = $this->sample();
        Http::fake([$this->portalUrl('accept-sample-file') => Http::response(['ok' => true])]);

        $this->post(route('samples.files.store', $sample), [
            'file' => UploadedFile::fake()->createWithContent('instant.txt', 'instant bytes'),
            'instantVis' => 'true',
        ])->assertCreated()
            ->assertJsonPath('data.0.visible_for_client', true);

        $sampleFile = SampleFile::query()->firstOrFail();
        $this->assertTrue($sampleFile->visible_for_client);
        Http::assertSent(fn (Request $request): bool => $request->url() === $this->portalUrl('accept-sample-file')
            && $request->hasFile('fileData', base64_encode('instant bytes'))
            && $request->hasFile('mesaHash', $sampleFile->hash_name));
    }

    public function test_visibility_changes_sync_exact_file_data_with_the_portal_even_for_an_authorized_project(): void
    {
        [$project, $sample] = $this->sample(['auth_status' => 1]);
        $sampleFile = $this->sampleFile($sample, false);
        Storage::disk('local')->put($sampleFile->storagePath(), 'portal bytes');
        app(PortalSettings::class)->set('acceptType', 'application/vnd.mesa+json');
        app(PortalSettings::class)->set('bearer', 'sample-file-token');
        Http::fake([$this->portalUrl('*') => Http::response(['ok' => true])]);

        $this->patchJson(route('samples.files.visibility.update', [$sample, $sampleFile]), [
            'visible_for_client' => true,
        ])->assertOk()
            ->assertJsonPath('data.0.visible_for_client', true);

        $this->assertDatabaseHas('samplefiles', ['id' => $sampleFile->id, 'visible_for_client' => 1]);
        $this->assertDatabaseHas('changetracker', [
            'type' => '27',
            'project' => $project->id,
            'sample' => $sample->id,
            'from' => 'Niet zichtbaar',
            'to' => 'Zichtbaar',
        ]);
        Http::assertSent(fn (Request $request): bool => $request->url() === $this->portalUrl('accept-sample-file')
            && $request->hasHeader('Accept', 'application/vnd.mesa+json')
            && $request->hasHeader('Authorization', 'sample-file-token')
            && $request->hasFile('fileData', base64_encode('portal bytes'))
            && $request->hasFile('mesaHash', $sampleFile->hash_name)
            && $request->hasFile('dataHash', md5('portal bytes'))
            && $request->hasFile('clientId', $sample->client)
            && $request->hasFile('sampleId', $sample->id)
            && $request->hasFile('originalFileName', $sampleFile->original_name));

        $this->patchJson(route('samples.files.visibility.update', [$sample, $sampleFile]), [
            'visible_for_client' => false,
        ])->assertOk()
            ->assertJsonPath('data.0.visible_for_client', false);

        $this->assertDatabaseHas('samplefiles', ['id' => $sampleFile->id, 'visible_for_client' => 0]);
        Http::assertSent(fn (Request $request): bool => $request->url() === $this->portalUrl('drop-sample-file')
            && $request->hasFile('sampleId', $sample->id)
            && $request->hasFile('mesaHash', $sampleFile->hash_name));
        Http::assertSentCount(2);
    }

    public function test_failed_portal_sync_keeps_visibility_unchanged_and_does_not_log_success(): void
    {
        [, $sample] = $this->sample();
        $sampleFile = $this->sampleFile($sample, false);
        Storage::disk('local')->put($sampleFile->storagePath(), 'portal bytes');
        Http::fake([
            $this->portalUrl('accept-sample-file') => Http::response(['message' => 'Portal unavailable.'], 503),
        ]);

        $this->patchJson(route('samples.files.visibility.update', [$sample, $sampleFile]), [
            'visible_for_client' => true,
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['visible_for_client']);

        $this->assertFalse($sampleFile->fresh()->visible_for_client);
        $this->assertDatabaseMissing('changetracker', ['type' => '27', 'sample' => $sample->id]);
    }

    public function test_deletion_removes_private_and_portal_files_but_preserves_legacy_history(): void
    {
        [$project, $sample] = $this->sample();
        $sampleFile = $this->sampleFile($sample, true);
        Storage::disk('local')->put($sampleFile->storagePath(), 'old bytes');
        Http::fake([$this->portalUrl('drop-sample-file') => Http::response(['ok' => true])]);
        $history = FileHistory::query()->create([
            'sample_id' => $sample->id,
            'samplefile_id' => $sampleFile->id,
            'hash_name' => $sampleFile->hash_name,
            'sent_by' => auth()->id(),
            'sent_to' => ['groups' => 'Quality', 'extra' => 'qa@example.test'],
            'sent_method' => 'filesender',
            'transaction_id' => null,
        ]);

        $this->deleteJson(route('samples.files.destroy', [$sample, $sampleFile]))
            ->assertOk()
            ->assertJsonPath('data', []);

        Storage::disk('local')->assertMissing($sampleFile->storagePath());
        $this->assertDatabaseMissing('samplefiles', ['id' => $sampleFile->id]);
        $this->assertDatabaseHas('filehistories', ['id' => $history->id, 'samplefile_id' => $sampleFile->id]);
        $this->assertDatabaseHas('changetracker', [
            'type' => '26',
            'project' => $project->id,
            'sample' => $sample->id,
            'event' => 'Bestand verwijderd',
            'to' => $sampleFile->original_name,
        ]);
        Http::assertSent(fn (Request $request): bool => $request->url() === $this->portalUrl('drop-sample-file')
            && $request->hasFile('sampleId', $sample->id)
            && $request->hasFile('mesaHash', $sampleFile->hash_name));
    }

    public function test_download_preview_and_listing_require_view_access_and_scope_files_to_the_sample(): void
    {
        [, $sample] = $this->sample();
        [, $otherSample] = $this->sample([], '26110802');
        $sampleFile = $this->sampleFile($sample, false);
        Storage::disk('local')->put($sampleFile->storagePath(), 'download bytes');

        $this->get(route('samples.files.download', [$sample, $sampleFile]))
            ->assertDownload($sampleFile->original_name);
        $this->get(route('samples.files.preview', [$sample, $sampleFile]))
            ->assertOk()
            ->assertHeaderContains('Content-Disposition', 'inline')
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeaderContains('Content-Security-Policy', 'sandbox');
        $this->getJson(route('samples.files.index', $sample))
            ->assertOk()
            ->assertJsonPath('data.0.id', $sampleFile->id);
        $this->getJson(route('samples.files.index', $otherSample))
            ->assertOk()
            ->assertJsonPath('data', []);
        $this->get(route('samples.files.download', [$otherSample, $sampleFile]))->assertNotFound();

        Gate::define('samples.view', fn (): bool => false);
        Gate::define('projects.view', fn (): bool => false);
        $this->getJson(route('samples.files.index', $sample))->assertForbidden();
        $this->get(route('samples.files.download', [$sample, $sampleFile]))->assertForbidden();
    }

    public function test_upload_and_mutations_validate_input_permission_and_nested_scope(): void
    {
        [, $sample] = $this->sample();
        [, $otherSample] = $this->sample([], '26110802');
        $sampleFile = $this->sampleFile($sample, false);

        $this->postJson(route('samples.files.store', $sample))->assertUnprocessable()
            ->assertJsonValidationErrors(['file']);
        $this->patchJson(route('samples.files.visibility.update', [$sample, $sampleFile]), [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['visible_for_client']);
        $this->patchJson(route('samples.files.visibility.update', [$otherSample, $sampleFile]), [
            'visible_for_client' => true,
        ])->assertNotFound();

        Gate::define('samples.view', fn (): bool => false);
        Gate::define('projects.view', fn (): bool => false);
        $this->post(route('samples.files.store', $sample), [
            'file' => UploadedFile::fake()->createWithContent('forbidden.txt', 'no'),
        ])->assertForbidden();
        $this->deleteJson(route('samples.files.destroy', [$sample, $sampleFile]))->assertForbidden();

        $this->assertDatabaseCount('samplefiles', 1);
        Http::assertSentCount(0);
    }

    public function test_view_permission_allows_file_management_without_sample_detail_edit_permission(): void
    {
        [, $sample] = $this->sample();
        Gate::define('samples.update', fn (): bool => false);

        $this->post(route('samples.files.store', $sample), [
            'file' => UploadedFile::fake()->createWithContent('analyst.txt', 'allowed'),
        ])->assertCreated()
            ->assertJsonPath('data.0.original_name', 'analyst.txt');

        $this->assertDatabaseHas('samplefiles', [
            'sample_id' => $sample->id,
            'original_name' => 'analyst.txt',
        ]);
    }

    public function test_mutations_return_423_when_the_sample_keyring_is_held_by_another_user(): void
    {
        [, $sample] = $this->sample();
        $holder = User::factory()->create(['enabled' => 1]);
        Keyring::factory()->forResource(KeyringType::Sample, $sample->id)->heldBy($holder)->create();

        $this->post(route('samples.files.store', $sample), [
            'file' => UploadedFile::fake()->createWithContent('locked.txt', 'locked bytes'),
        ], ['Accept' => 'application/json'])->assertStatus(423);

        $this->assertDatabaseCount('samplefiles', 0);
        Storage::disk('local')->assertDirectoryEmpty(SampleFile::STORAGE_DIRECTORY);
    }

    public function test_delivery_history_records_the_legacy_contract_and_marks_the_file_as_sent(): void
    {
        [, $sample] = $this->sample();
        $sampleFile = $this->sampleFile($sample, true);

        $history = app(RecordSampleFileDelivery::class)->handle(
            $sampleFile,
            'report',
            987,
            ['groups' => 'Quality;Management', 'extra' => 'extra@example.test'],
        );

        $this->assertTrue($sampleFile->fresh()->sent_to_client);
        $this->assertSame($sample->id, $history->sample_id);
        $this->assertSame($sampleFile->id, $history->samplefile_id);
        $this->assertSame('report', $history->sent_method);
        $this->assertSame(987, $history->transaction_id);
        $this->assertSame(
            ['groups' => 'Quality;Management', 'extra' => 'extra@example.test'],
            $history->sent_to,
        );
        $this->assertSame(auth()->id(), $history->sent_by);
    }

    /**
     * @return array{Project, Sample}
     */
    private function sample(array $projectAttributes = [], string $barcode = '26110801'): array
    {
        $project = Project::query()->create([
            'client' => 42,
            'subclient' => 0,
            'project_name' => 'Sample files',
            ...$projectAttributes,
        ]);
        $sample = Sample::query()->create([
            'barcode' => $barcode,
            'follow_no' => 1,
            'description' => 'Sample with files',
            'sampling_method' => 1,
            'date_registered' => '1794128400',
            'registered_by' => auth()->id(),
            'client' => 42,
            'subclient' => 0,
            'project' => $project->id,
            'custom_fields' => '{}',
            'predicted_end' => -1,
            'sample_innoculated' => '',
            'analyses_data' => '[]',
            'sample_type' => 'S',
            'sample_extra' => '{}',
        ]);

        return [$project, $sample];
    }

    private function sampleFile(Sample $sample, bool $visible): SampleFile
    {
        return SampleFile::query()->create([
            'sample_id' => $sample->id,
            'client_id' => $sample->client,
            'hash_name' => 'hash-'.$sample->id,
            'original_name' => 'certificate-'.$sample->id.'.pdf',
            'visible_for_client' => $visible,
            'sent_to_client' => false,
        ]);
    }

    private function portalUrl(string $endpoint): string
    {
        return rtrim((string) config('services.glass.base_url'), '/').'/'.ltrim($endpoint, '/');
    }
}
