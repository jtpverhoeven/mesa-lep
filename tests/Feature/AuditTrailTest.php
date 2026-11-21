<?php

namespace Tests\Feature;

use App\ChangeTracking\ChangeTracker;
use App\Models\Assay;
use App\Models\AssuranceForm;
use App\Models\Client;
use App\Models\Confirmation;
use App\Models\ConfKeyStore;
use App\Models\Export;
use App\Models\FileHistory;
use App\Models\Media;
use App\Models\Note;
use App\Models\Profile;
use App\Models\Project;
use App\Models\Result;
use App\Models\Sample;
use App\Models\SampleAnalysis;
use App\Models\SampleFile;
use App\Models\SampleProcedure;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class AuditTrailTest extends TestCase
{
    private User $analyst;

    private Sample $sample;

    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');
        foreach (glob(database_path('migrations/*.php')) as $path) {
            (require $path)->up();
        }
        $this->analyst = User::factory()->create(['enabled' => 1, 'name' => 'analist']);
        Profile::create(['user_id' => $this->analyst->id, 'first_name' => 'Anna', 'last_name' => 'Analist']);
        $this->actingAs($this->analyst);
        Gate::define('assurance-form.view', fn () => true);
        Gate::define('projects.view', fn () => true);
    }

    public function test_an_unknown_barcode_is_reported(): void
    {
        $this->getJson(route('audit-trail.data', ['barcode' => '99999999']))->assertNotFound();
        $this->get(route('audit-trail.print', ['barcode' => '99999999']))->assertNotFound();
    }

    public function test_the_audit_trail_needs_the_assurance_form_permission_and_hides_the_project_without_project_access(): void
    {
        $this->inoculatedSample();

        Gate::define('projects.view', fn () => false);
        $this->getJson(route('audit-trail.data', ['barcode' => '26091000']))->assertOk()->assertJsonPath('data.project', null);

        Gate::define('assurance-form.view', fn () => false);
        $this->getJson(route('audit-trail.data', ['barcode' => '26091000']))->assertForbidden();
    }

    public function test_a_sample_that_was_not_inoculated_only_has_the_general_part(): void
    {
        $this->plainSample('26091001', '');
        app(ChangeTracker::class)->changed(2, project: $this->sample->project, sample: $this->sample->id, event: 'Monster aangemaakt', userId: $this->analyst->id);

        $data = $this->getJson(route('audit-trail.data', ['barcode' => '26091001']))->assertOk()->json('data');

        $this->assertFalse($data['sample']['assurance']['available']);
        $this->assertSame('not_inoculated', $data['sample']['assurance']['reason']);
        $this->assertNull($data['sample']['analysis']);
        $this->assertNull($data['sample']['general']['inoculated']);
        $this->assertSame('Anna Analist', $data['sample']['revisions']['sample'][0]['user']);
        $this->assertSame('Bakkerij Jansen', $data['sample']['client']['name']);
    }

    public function test_an_inoculated_sample_without_an_assurance_form_says_so(): void
    {
        $this->plainSample('26091002', (string) CarbonImmutable::create(2026, 9, 15, 9, 0, 0, 'Europe/Amsterdam')->timestamp);

        $assurance = $this->getJson(route('audit-trail.data', ['barcode' => '26091002']))->assertOk()->json('data.sample.assurance');

        $this->assertSame('no_assurance_form', $assurance['reason']);
    }

    public function test_the_general_part_follows_the_legacy_audit_trail(): void
    {
        $this->inoculatedSample();

        $sample = $this->getJson(route('audit-trail.data', ['barcode' => '26091000.2']))->assertOk()->json('data.sample');

        $this->assertSame('Mevr. A B Jansen', $sample['client']['contact']);
        $this->assertSame('Dorpsstraat 1', $sample['client']['street']);
        $this->assertSame(['id', 'reference', 'name', 'number_of_samples', 'revision', 'authorized_on', 'authorized_by', 'reported_on', 'reported_by', 'notes'], array_keys($sample['project']));
        $this->assertSame('P-100', $sample['project']['reference']);
        $this->assertSame(1, $sample['project']['number_of_samples']);
        $this->assertSame('15-09-2026', $sample['project']['authorized_on']);
        $this->assertSame('Anna Analist', $sample['project']['authorized_by']);
        $this->assertNull($sample['project']['reported_on']);
        $this->assertSame([['label' => 'Project', 'text' => 'Projectopmerking']], $sample['project']['notes']);
        $this->assertSame('14-09-2026', $sample['general']['date_receive']);
        $this->assertSame('10:30', $sample['general']['time_receive']);
        $this->assertSame('15-09-2026 09:00', $sample['general']['inoculated']);
        $this->assertSame('Klant', $sample['general']['sampling_by']);
        $this->assertSame('Grijpmonster', $sample['general']['sampling_method']);
        $this->assertSame('Details van het monster', $sample['general']['details']);
        $this->assertSame('Bak 4', $sample['general']['stored_in']);
        $this->assertSame('Let op: koeling', $sample['general']['notes'][0]['text']);
        $this->assertSame('15-09-2026', $sample['assurance']['form']['date']);
    }

    public function test_the_assurance_form_values_are_flagged_with_their_explanations(): void
    {
        $this->inoculatedSample();

        $analysis = $this->getJson(route('audit-trail.data', ['barcode' => '26091000']))->assertOk()->json('data.sample.analysis');

        $this->assertSame('Anna Analist', $analysis['set_up_by']);
        $this->assertSame('Onbekend', $analysis['poured_by']);

        [$pfz, $tubes] = $analysis['accumulation'];
        $this->assertSame('PFZ', $pfz['media']);
        $this->assertTrue($pfz['tht']['out_of_specification']);
        $this->assertSame('Goedgekeurd door kwaliteit', $pfz['tht']['note']);
        $this->assertFalse($tubes['tht']['flagged']);
        $this->assertSame('', $tubes['tht']['note']);

        $medium = $analysis['media'][0];
        $this->assertSame('PCA', $medium['name']);
        $this->assertTrue($medium['tht']['out_of_specification']);
        $this->assertTrue($medium['tht']['used_after_expiry']);
        $this->assertSame('Uitleg PCA', $medium['tht']['note']);
        $this->assertSame('TTC', $medium['supplements'][0]['name']);
        $this->assertFalse($medium['supplements'][0]['tht']['out_of_specification']);
        $this->assertTrue($medium['supplements'][0]['tht']['used_after_expiry']);

        $material = $analysis['material'][0];
        $this->assertSame('Waterbad', $material['name']);
        $this->assertSame('°C', $material['unit']);
        $this->assertSame('70', $material['value']['value']);
        $this->assertTrue($material['value']['out_of_specification']);
        $this->assertFalse($material['value']['used_after_expiry']);

        $this->assertSame(['volg' => 1, 'name' => 'Salmonella', 'date_in' => '15-09-2026', 'time_in' => '09:15', 'date_out' => '17-09-2026', 'time_out' => '09:00', 'date_read' => '17-09-2026', 'time_read' => '13:00', 'read_by' => 'Anna Analist'], $analysis['dates'][0]);
        $this->assertSame(['volg' => 1, 'name' => 'Salmonella', 'accredited' => 'Q', 'technique' => 'Plaat', 'internal_reference' => 'INT-7', 'conformity' => 'ISO', 'reference_method' => 'ISO 6579', 'assay_id' => 5, 'revision' => 2], $analysis['assays'][0]);
    }

    public function test_confirmations_and_results_are_listed_per_analysis(): void
    {
        $this->inoculatedSample();

        $analysis = $this->getJson(route('audit-trail.data', ['barcode' => '26091000']))->assertOk()->json('data.sample.analysis');

        $this->assertSame([['volg' => 1, 'name' => 'Salmonella', 'performed' => true]], $analysis['confirmations']);
        $this->assertSame([['volg' => 1, 'note' => 'Let op de racetrack']], $analysis['confirmation_notes']);

        [$confirmationMedium, $supportMedium] = $analysis['confirmation_media'];
        $this->assertSame(['df' => '1', 'rep' => 0, 'name' => 'Bevestigingsplaat', 'set_up_on' => '15-09-2026', 'set_up_by' => 'Anna Analist', 'read_on' => '17-09-2026', 'read_by' => 'Onbekend'], collect($confirmationMedium)->only(['df', 'rep', 'name', 'set_up_on', 'set_up_by', 'read_on', 'read_by'])->all());
        $this->assertFalse($confirmationMedium['tht']['flagged']);
        $this->assertSame(['name' => 'Ondersteunend medium', 'set_up_on' => 'NVT', 'read_by' => 'NVT'], collect($supportMedium)->only(['name', 'set_up_on', 'read_by'])->all());

        $confirmationResult = $analysis['confirmation_results'][0];
        $this->assertSame(['+', '-', '', '', ''], $confirmationResult['kve']);
        $this->assertSame(2, $confirmationResult['tested']);
        $this->assertSame(1, $confirmationResult['positive']);
        $this->assertSame('0', $confirmationResult['positive_control']);
        $this->assertNull($confirmationResult['negative_control']);

        $this->assertSame(['volg' => 1, 'counts' => ['12', '1,2', 'n.u.', 'n.u.', 'n.u.', 'n.u.', 'n.u.', 'n.u.', 'n.u.', 'n.u.', 'n.u.'], 'end_result' => '120', 'ct_value' => '30'], $analysis['results'][0]);
    }

    public function test_global_confirmations_are_listed_once_and_unrequested_ones_are_skipped(): void
    {
        $this->inoculatedSample(['confirmation_type' => 0], confirmationInUse: ['global' => [0 => [301 => true]]], racetrack: ['global' => [0 => [['+']]]]);

        $analysis = $this->getJson(route('audit-trail.data', ['barcode' => '26091000']))->assertOk()->json('data.sample.analysis');

        $this->assertSame(['Globaal'], collect($analysis['confirmation_results'])->pluck('df')->unique()->values()->all());
        $this->assertCount(1, $analysis['confirmation_results']);

        SampleAnalysis::query()->update(['conf_requested' => 0]);

        $analysis = $this->getJson(route('audit-trail.data', ['barcode' => '26091000']))->json('data.sample.analysis');
        $this->assertFalse($analysis['confirmations'][0]['performed']);
        $this->assertSame([], $analysis['confirmation_media']);
    }

    public function test_revisions_of_the_analysis_project_sample_and_assurance_form_are_included(): void
    {
        $this->inoculatedSample();
        $changes = app(ChangeTracker::class);
        $changes->changed(3, project: $this->sample->project, event: 'Project aangemaakt', userId: $this->analyst->id);
        $changes->changed(1, project: $this->sample->project, sample: $this->sample->id, said: SampleAnalysis::query()->value('id'), event: 'Resultaat gewijzigd', from: '1', to: '2', userId: $this->analyst->id);
        $changes->changed(13, assuranceForm: AssuranceForm::query()->value('id'), event: 'Veld gewijzigd', from: 'a', to: 'b', userId: $this->analyst->id);

        $sample = $this->getJson(route('audit-trail.data', ['barcode' => '26091000']))->assertOk()->json('data.sample');

        $this->assertSame(['Project aangemaakt', 'Resultaat gewijzigd'], collect($sample['revisions']['project'])->pluck('event')->all());
        $this->assertSame(['Resultaat gewijzigd'], collect($sample['revisions']['sample'])->pluck('event')->all());
        $this->assertSame(['Resultaat gewijzigd'], collect($sample['analysis']['assay_revisions'][0]['revisions'])->pluck('event')->all());
        $this->assertEquals(['from' => 'a', 'to' => 'b', 'user' => 'Anna Analist'], collect($sample['revisions']['assurance_form'][0])->only(['from', 'to', 'user'])->all());
    }

    public function test_the_project_audit_trail_collects_what_happened_to_the_project(): void
    {
        $this->inoculatedSample();
        $project = Project::query()->first();
        Note::factory()->forNotable($this->sample)->create(['user_id' => $this->analyst->id, 'body' => '<p>Interne notitie</p>']);
        Note::factory()->report()->forNotable($this->sample)->create(['body' => '<p>Rapportnotitie monster</p>']);
        $export = Export::factory()->create(['project' => $project->id, 'client' => $project->client, 'transaction' => 11, 'type' => 2, 'revision' => 1, 'print_version' => 3, 'temporary' => 1]);
        $file = SampleFile::factory()->create(['sample_id' => $this->sample->id, 'original_name' => 'certificaat.pdf', 'visible_for_client' => true]);
        FileHistory::factory()->create(['sample_id' => $this->sample->id, 'samplefile_id' => $file->id, 'sent_by' => $this->analyst->id, 'sent_to' => ['groups' => 'Laboratorium', 'extra' => 'klant@example.test'], 'sent_method' => 'report']);
        $changes = app(ChangeTracker::class);
        $changes->changed(3, project: $project->id, event: 'Project aangemaakt', userId: $this->analyst->id);
        $changes->changed(21, project: $project->id, event: 'Project verstuurd naar portal-mailer', userId: $this->analyst->id);

        $trail = $this->getJson(route('audit-trail.data', ['barcode' => '26091000']))->assertOk()->json('data.project');

        $this->assertSame('P-100', $trail['overview']['reference']);
        $this->assertSame('Anna Analist', $trail['overview']['created_by']);
        $this->assertSame([['source' => 'Portal', 'count' => 1]], $trail['overview']['sources']);
        $this->assertSame(['Geautoriseerd', 'Anna Analist'], [$trail['overview']['status'], $trail['overview']['authorized_by']]);
        $this->assertSame('Bakkerij Jansen', $trail['overview']['client']['name']);
        $this->assertSame(['26091000'], collect($trail['samples'])->pluck('barcode')->all());
        $this->assertSame(1, $trail['samples'][0]['analyses']);
        $this->assertSame(['Project', 'Monster 26091000'], collect($trail['notes']['report'])->pluck('label')->all());
        $this->assertSame('Interne notitie', $trail['notes']['internal'][0]['text']);
        $this->assertSame([['transaction' => 11, 'type' => 'Voorlopig rapport', 'reference' => 'P-100.1.3', 'temporary' => true]], collect($trail['reports'])->map(fn (array $report): array => collect($report)->only(['transaction', 'type', 'reference', 'temporary'])->all())->all());
        $this->assertSame(['Project verstuurd naar portal-mailer'], collect($trail['sendings'])->pluck('event')->all());
        $this->assertSame('certificaat.pdf', $trail['files'][0]['name']);
        $this->assertSame('Laboratorium, klant@example.test', $trail['files'][0]['deliveries'][0]['recipients']);
        $this->assertCount(2, $trail['revisions']);
        $this->assertSame($export->id, $trail['reports'][0]['id']);
    }

    public function test_the_print_page_renders_the_same_trail(): void
    {
        $this->inoculatedSample();

        $this->get(route('audit-trail.print', ['barcode' => '26091000']))
            ->assertOk()
            ->assertViewIs('audit-trail.print')
            ->assertViewHas('trail', fn (array $trail): bool => $trail['barcode'] === '26091000' && $trail['project'] !== null)
            ->assertSee('audit-trail-print', false);
        $this->get(route('audit-trail', ['barcode' => '26091000']))->assertOk()->assertSee('audit-trail', false);
    }

    private function plainSample(string $barcode, string $inoculated): void
    {
        $client = Client::create(['name' => 'Bakkerij Jansen', 'title' => 'Mevr.', 'fname' => 'A', 'mname' => '', 'lname' => 'B Jansen', 'street_name' => 'Dorpsstraat', 'street_number' => '1', 'place' => 'Utrecht']);
        $project = Project::create(['client' => $client->id, 'subclient' => 0, 'reference' => 'P-100', 'project_name' => 'Waterproject', 'custom_fields' => json_encode(['project_monster' => '14-09-2026', 'project_ontvangst' => '14-09-2026', 'project_tijd_ontvangst' => '10:30']), 'added_by' => $this->analyst->id, 'project_date' => (string) CarbonImmutable::create(2026, 9, 14, 11, 0, 0, 'Europe/Amsterdam')->timestamp]);
        $procedure = SampleProcedure::create(['name' => 'Grijpmonster', 'fields' => json_encode(['door' => 'Klant'])]);
        $this->sample = Sample::create([
            'barcode' => $barcode, 'follow_no' => 1, 'description' => 'Waterstaal', 'sampling_method' => $procedure->id,
            'date_registered' => '1788998400', 'registered_by' => $this->analyst->id, 'client' => $client->id, 'subclient' => 0,
            'project' => $project->id, 'custom_fields' => json_encode(['details' => 'Details van het monster']), 'predicted_end' => -1,
            'sample_innoculated' => $inoculated, 'analyses_data' => '[]', 'stored_in' => 'Bak 4', 'source' => 3,
        ]);
    }

    /** A sample with one confirmed analysis on a day that has an assurance form full of flagged values. */
    private function inoculatedSample(array $assay = [], ?array $confirmationInUse = null, ?array $racetrack = null): void
    {
        $this->plainSample('26091000', (string) CarbonImmutable::create(2026, 9, 15, 9, 0, 0, 'Europe/Amsterdam')->timestamp);
        $project = Project::query()->first();
        $project->update(['auth_status' => 1, 'auth_on' => CarbonImmutable::create(2026, 9, 15, 12, 0, 0, 'Europe/Amsterdam')->timestamp, 'auth_by' => $this->analyst->id]);
        Note::factory()->report()->forNotable($project)->create(['body' => '<p>Projectopmerking</p>']);
        Note::factory()->forNotable($this->sample)->create(['user_id' => $this->analyst->id, 'body' => '<p>Let op: koeling</p>']);

        $pca = Media::forceCreate(['id' => 101, 'name' => 'PCA', 'type' => 1, 'supplements' => json_encode([['name' => 'TTC', 'supplementId' => '4']])]);
        $bath = Media::forceCreate(['id' => 201, 'name' => 'Waterbad', 'type' => 3, 'supplements' => '°C', 'acceptable_range' => '>=40 <=60']);
        $confirmationMedium = Media::forceCreate(['id' => 301, 'name' => 'Bevestigingsplaat', 'type' => 1]);
        $supportMedium = Media::forceCreate(['id' => 302, 'name' => 'Ondersteunend medium', 'type' => 1]);

        Assay::forceCreate(['id' => 4, 'original_id' => 5, 'name' => 'Salmonella', 'type_base' => 1, 'media_id' => '[]', 'dillution' => 0, 'replicates' => 1, 'confirmation' => 0, 'confirmation_script' => '[]', 'type' => 1, 'meta_assays' => '', 'max_count' => 300, 'min_count' => 10, 'script' => '', 'custom_fields' => '{}', 'duration' => '48', 'start_from' => '', 'article_code' => '']);
        $assay = Assay::forceCreate(['id' => 9, 'original_id' => 5, 'name' => 'Salmonella', 'type_base' => 1, 'media_id' => json_encode([$pca->id, $bath->id]), 'dillution' => 0, 'replicates' => 1,
            'confirmation' => 1, 'confirmation_type' => 1,
            'confirmation_script' => json_encode([['mediaId' => $confirmationMedium->id, 'chainId' => 1, 'disposition' => '']]),
            'confirmation_support' => json_encode([['mediaId' => $supportMedium->id, 'chainId' => 1]]),
            'type' => 1, 'meta_assays' => '', 'max_count' => 300, 'min_count' => 10, 'script' => '', 'duration' => '48', 'start_from' => '', 'article_code' => '',
            'custom_fields' => json_encode(['accred' => 'Q', 'techniek' => 'Plaat', 'internrefnummer' => 'INT-7', 'conform' => 'ISO', 'referentiemethode' => 'ISO 6579']),
            ...$assay]);
        $analysis = SampleAnalysis::create(['profile_group' => 1, 'sample' => $this->sample->id, 'follow_number' => 1, 'profile' => 0, 'assay' => 0, 'predicted_end' => -1, 'assay_base' => $assay->id, 'conf_requested' => 1, 'is_ready' => 0, 'project' => $project->id, 'storedResult' => ['output' => ['kve' => '120']]]);

        foreach ([[1, 0, ['kve' => '12']], ['0.1', 0, ['kve' => '1']], ['0.1', 1, ['kve' => '2', 'Ctwaarde' => '30']]] as [$df, $rep, $data]) {
            Result::create(['sample' => $this->sample->id, 'sa_id' => $analysis->id, 'follow_no' => 1, 'profile' => 0, 'assay' => 0, 'assay_base' => $assay->id, 'roaming_id' => 0, 'df' => $df, 'rep' => $rep, 'data' => $data]);
        }

        Confirmation::create(['said' => $analysis->id, 'note' => 'Let op de racetrack',
            'in_use' => $confirmationInUse ?? ['1' => [0 => [301 => true, 302 => true]]],
            'racetrack' => $racetrack ?? ['1' => [0 => [['+'], ['-']]]],
            'metadata' => ['1' => [0 => ['ratio' => 0.5, 0 => ['301_inzet' => '15-09-2026', '301_inzet_user' => $this->analyst->id, '301_aflees' => '17-09-2026']]]]]);
        ConfKeyStore::create(['innocdate' => '15-09-2026', 'media' => 301, 'param' => 'poscontrol', 'value' => '0']);

        $formDay = CarbonImmutable::create(2026, 9, 15, 0, 0, 0, 'Europe/Amsterdam')->timestamp;
        AssuranceForm::create(['date' => (string) $formDay, 'is_complete' => 1, 'data' => json_encode([
            'users_available_at_time' => [(string) $this->analyst->id => 'Anna Analist'],
            0 => ['ingezet' => (string) $this->analyst->id, 'gegoten' => '', 'instoof' => '09:15'],
            1 => ['48' => ['datum_uitstoof' => '17-09-2026', 'tijd_uitstoof' => '09:00', 'datum_aflezen' => '17-09-2026', 'tijd_aflezen' => '13:00', 'afgelezen_door' => (string) $this->analyst->id]],
            2 => ['tht_pfz' => '01-09-2026', 'tht_pfz_buizen' => '30-09-2026', 'tht_bpw' => 'nvt'],
            3 => ['101' => '01-09-2026', 'extra_101_4' => '30-09-2026', '201' => '70', '301' => '30-09-2026'],
            4 => ['b2_tht_pfz' => 'Goedgekeurd door kwaliteit', 'b3_101' => 'Uitleg PCA', 'b2_tht_pfz_buizen' => 'Hoort niet getoond te worden'],
            5 => ['wasOutOfDateHere' => [101 => [$this->sample->id => ['sampleId' => $this->sample->id, 'sampleBarcode' => '26091000']]]],
        ], JSON_FORCE_OBJECT)]);
    }
}
