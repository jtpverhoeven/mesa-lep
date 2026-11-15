<?php

namespace Tests\Feature;

use App\Models\Assay;
use App\Models\Client;
use App\Models\ClientCategory;
use App\Models\Project;
use App\Models\Sample;
use App\Models\SampleAnalysis;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Testing\TestResponse;
use OpenSpout\Reader\XLSX\Reader;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class BillingTest extends TestCase
{
    private int $sampleId = 0;

    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');
        foreach (glob(database_path('migrations/*.php')) as $path) {
            (require $path)->up();
        }
        $this->actingAs(User::factory()->create(['enabled' => 1]));
    }

    public function test_billing_requires_project_view_permission(): void
    {
        Gate::define('projects.view', fn () => false);

        $this->get(route('billing.index'))->assertForbidden();
        $this->postJson(route('billing.overview'))->assertForbidden();
        $this->get(route('billing.export', ['projects' => [1]]))->assertForbidden();
    }

    public function test_scanning_appends_projects_in_scan_order_without_duplicates(): void
    {
        $this->allowBilling();
        $this->project(1);
        $this->project(2);
        $this->sample(1, ['barcode' => 'BC-1']);
        $this->sample(2, ['barcode' => 'BC-2', 'tht_code' => 'THT-0002']);

        $this->scan([1], 'THT-0002')->assertJsonPath('status', 'ok')->assertJsonPath('projects', [1, 2]);
        $this->scan([2, 1], ' BC-1 ')->assertJsonPath('projects', [2, 1]);
        $this->scan([2, 999])->assertJsonPath('projects', [2]);
    }

    #[DataProvider('shorthandScans')]
    public function test_short_scans_follow_legacy_sample_number_rules(string $barcode, ?int $expectedProject): void
    {
        $this->allowBilling();
        $this->project(1);
        $this->project(2);
        $this->project(3);
        $this->project(4);
        $this->sample(1, ['follow_no' => 12, 'sample_type' => 'S']);
        $this->sample(2, ['follow_no' => 12, 'sample_type' => 'L']);
        $this->sample(3, ['follow_no' => 34, 'sample_type' => 'R']);
        $this->sample(4, ['barcode' => '123456', 'follow_no' => 99]);

        $response = $this->scan([], $barcode);

        $response->assertJsonPath('status', $expectedProject ? 'ok' : 'barcode_not_found');
        $response->assertJsonPath('projects', $expectedProject ? [$expectedProject] : []);
    }

    public static function shorthandScans(): array
    {
        return [
            'digits find normal samples' => ['12', 1],
            'L prefix finds legionella samples' => ['L12', 2],
            'suffix after a dot is ignored' => ['12.7', 1],
            'rodac samples are never found by shorthand' => ['34', null],
            'non numeric shorthand finds nothing' => ['AB', null],
            'longer scans match the full barcode' => ['123456', 4],
        ];
    }

    public function test_unknown_barcode_keeps_the_current_overview(): void
    {
        $this->allowBilling();
        $this->project(1);
        $this->sample(1);

        $this->scan([1], 'UNKNOWN-CODE')
            ->assertJsonPath('status', 'barcode_not_found')
            ->assertJsonPath('projects', [1])
            ->assertJsonPath('project_rows.0.id', 1);
    }

    public function test_projects_of_different_clients_are_rejected(): void
    {
        $this->allowBilling();
        $this->project(1, ['client' => 1]);
        $this->project(2, ['client' => 2]);
        $this->sample(2, ['barcode' => 'OTHER-CLIENT']);

        $this->scan([1], 'OTHER-CLIENT')
            ->assertJsonPath('status', 'client_mismatch')
            ->assertJsonPath('projects', [])
            ->assertJsonPath('client', null);
        $this->get(route('billing.export', ['projects' => [1, 2]]))->assertUnprocessable();
    }

    public function test_overview_lists_client_and_project_rows(): void
    {
        $this->allowBilling();
        $client = Client::create(['name' => 'Acme', 'debit_number' => 'D-42']);
        $client->categories()->attach(ClientCategory::create(['name' => 'Zuivel']));
        $client->categories()->attach(ClientCategory::create(['name' => 'Agro']));
        $this->project(1, ['client' => $client->id, 'reference' => '24-001', 'revision' => 2, 'project_name' => 'Batch A',
            'custom_fields' => json_encode(['project_monster' => '01-06-2024', 'project_ontvangst' => '02-06-2024'])]);
        $this->sample(1, ['sample_innoculated' => (string) $this->at('2024-06-04 09:30')]);
        $this->sample(1, ['sample_innoculated' => (string) $this->at('2024-06-05 10:15')]);
        $this->sample(1, ['sample_innoculated' => '0']);

        $response = $this->scan([1]);

        $response->assertJsonPath('client', ['name' => 'Acme', 'debit_number' => 'D-42', 'categories' => ['Zuivel', 'Agro']]);
        $response->assertJsonPath('project_rows.0.reference', '24-001');
        $response->assertJsonPath('project_rows.0.revision', 2);
        $response->assertJsonPath('project_rows.0.project_name', 'Batch A');
        $response->assertJsonPath('project_rows.0.samples_count', 3);
        $response->assertJsonPath('project_rows.0.sampling_date', '01-06-2024');
        $response->assertJsonPath('project_rows.0.received_date', '02-06-2024');
        $response->assertJsonPath('project_rows.0.inoculation_date', "04-06-2024 09:30\n05-06-2024 10:15");
    }

    public function test_sample_types_count_weekend_and_holiday_surcharges_by_inoculation_date(): void
    {
        $this->allowBilling();
        $this->project(1);
        $this->project(2);
        $saturday = (string) $this->at('2024-06-01 10:00');
        $sunday = (string) $this->at('2024-06-02 10:00');
        $holiday = (string) $this->at('2024-12-25 10:00');
        $weekday = (string) $this->at('2024-06-04 10:00');
        $this->sample(1, ['sample_type' => 'S', 'sample_innoculated' => $saturday]);
        $this->sample(1, ['sample_type' => 'S', 'sample_innoculated' => '0']);
        $this->sample(1, ['sample_type' => 'S', 'tht_code' => 'THT', 'sample_innoculated' => $sunday]);
        $this->sample(2, ['sample_type' => 'R', 'tht_code' => 'THT', 'sample_innoculated' => $holiday]);
        $this->sample(2, ['sample_type' => 'L', 'sample_innoculated' => $weekday]);
        $this->sample(2, ['sample_type' => 'X', 'sample_innoculated' => $saturday]);

        $this->scan([1, 2])->assertJsonPath('sample_types', [
            ['type' => 'S', 'header' => 'Normaal', 'count' => 2, 'count50' => 1, 'count100' => 0],
            ['type' => 'T', 'header' => 'THT', 'count' => 1, 'count50' => 0, 'count100' => 1],
            ['type' => 'R', 'header' => 'Rodac', 'count' => 1, 'count50' => 0, 'count100' => 1],
            ['type' => 'L', 'header' => 'Legionella', 'count' => 1, 'count50' => 0, 'count100' => 0],
        ]);
    }

    public function test_articles_group_billable_assays_and_count_positives_over_all_analyses(): void
    {
        $this->allowBilling();
        $this->project(1);
        $this->project(2, ['client' => 2]);
        $sample = $this->sample(1);
        $confirmed = $this->assay(['name' => 'salmonella', 'article_code' => 'ART-1']);
        $plain = $this->assay(['name' => 'Aerobe kiemgetal', 'article_code' => '']);
        $unconfirmed = $this->assay(['name' => 'Listeria', 'article_code' => 'ART-3']);
        $notBillable = $this->assay(['name' => 'Intern', 'billable' => 0]);
        $this->analysis($sample, $confirmed, ['conf_requested' => 1, 'storedResult' => ['hidden' => ['detected' => true]]]);
        $this->analysis($sample, $confirmed, ['storedResult' => ['hidden' => ['detected' => true]]]);
        $this->analysis($sample, $confirmed, ['storedResult' => ['hidden' => ['detected' => false]]]);
        $this->analysis($sample, $plain);
        $this->analysis($sample, $unconfirmed, ['storedResult' => ['hidden' => ['detected' => true]]]);
        $this->analysis($sample, $notBillable);
        $this->analysis($this->sample(2), $plain);

        $this->scan([1])->assertJsonPath('articles', [
            ['assay_base' => $plain->id, 'name' => 'Aerobe kiemgetal', 'article_code' => '-', 'total' => 1, 'conf_requested_count' => 0, 'positives' => null],
            ['assay_base' => $unconfirmed->id, 'name' => 'Listeria', 'article_code' => 'ART-3', 'total' => 1, 'conf_requested_count' => 0, 'positives' => null],
            ['assay_base' => $confirmed->id, 'name' => 'salmonella', 'article_code' => 'ART-1', 'total' => 3, 'conf_requested_count' => 1, 'positives' => 2],
        ]);
    }

    public function test_export_downloads_the_overview_as_a_single_xlsx_sheet(): void
    {
        $this->allowBilling();
        $client = Client::create(['name' => 'Acme', 'debit_number' => 'D-42']);
        $client->categories()->attach(ClientCategory::create(['name' => 'Zuivel']));
        $this->project(1, ['client' => $client->id, 'reference' => '24-001']);
        $sample = $this->sample(1, ['sample_type' => 'S', 'sample_innoculated' => (string) $this->at('2024-06-01 10:00')]);
        $this->analysis($sample, $this->assay(['name' => 'Salmonella', 'article_code' => 'ART-1']));

        $response = $this->get(route('billing.export', ['projects' => [1]]));

        $response->assertOk()->assertDownload('Facturatie Acme '.now('Europe/Amsterdam')->format('d-m-Y').'.xlsx');
        $rows = $this->readSheet($response->getFile()->getPathname());
        $this->assertContains(['Acme', 'Debiteur nummer: D-42', 'Klant categorie: Zuivel'], $rows);
        $this->assertContains('MAZ-L 24-001', array_merge(...$rows));
        $this->assertContains(['Normaal', 1, 1, 0], $rows);
        $this->assertContains(['Salmonella', 'ART-1', 1, 0], $rows);
    }

    public function test_export_requires_projects(): void
    {
        $this->allowBilling();

        $this->get(route('billing.export'))->assertSessionHasErrors('projects');
        $this->get(route('billing.export', ['projects' => [999]]))->assertUnprocessable();
    }

    private function allowBilling(): void
    {
        Gate::define('projects.view', fn () => true);
    }

    private function scan(array $projects, ?string $barcode = null): TestResponse
    {
        return $this->postJson(route('billing.overview'), ['projects' => $projects, 'barcode' => $barcode])->assertOk();
    }

    private function at(string $time): int
    {
        return Carbon::parse($time, 'Europe/Amsterdam')->getTimestamp();
    }

    private function project(int $id, array $attributes = []): Project
    {
        return Project::forceCreate([
            'id' => $id, 'client' => 1, 'subclient' => 0, 'project_name' => 'Project '.$id, 'reference' => (string) $id, ...$attributes,
        ]);
    }

    private function sample(int $project, array $attributes = []): Sample
    {
        $id = ++$this->sampleId;

        return Sample::forceCreate([
            'id' => $id, 'project' => $project, 'client' => 1, 'subclient' => 0,
            'barcode' => 'SAMPLE-'.$id, 'follow_no' => 1000 + $id, 'description' => 'Sample', 'sampling_method' => 0,
            'date_registered' => '1700000000', 'registered_by' => 7, 'custom_fields' => '{}',
            'predicted_end' => 0, 'sample_innoculated' => '0', 'analyses_data' => '{}', ...$attributes,
        ]);
    }

    private function assay(array $attributes): Assay
    {
        return Assay::create([
            'original_id' => 0, 'name' => 'Assay', 'type_base' => 1, 'media_id' => '[]',
            'dillution' => 1, 'replicates' => 1, 'confirmation' => 0, 'confirmation_script' => '',
            'type' => 1, 'meta_assays' => '', 'max_count' => 300, 'min_count' => 10,
            'script' => '', 'custom_fields' => '{}', 'duration' => '2', 'start_from' => 'r',
            'article_code' => '', ...$attributes,
        ]);
    }

    private function analysis(Sample $sample, Assay $assay, array $attributes = []): SampleAnalysis
    {
        return SampleAnalysis::forceCreate([
            'sample' => $sample->id, 'project' => $sample->project, 'profile_group' => 1, 'follow_number' => 1,
            'profile' => 0, 'assay' => $assay->id, 'assay_base' => $assay->id, 'predicted_end' => 0, 'project_order' => 1,
            ...$attributes,
        ]);
    }

    /**
     * @return list<list<mixed>>
     */
    private function readSheet(string $path): array
    {
        $reader = new Reader;
        $reader->open($path);
        $rows = [];
        foreach ($reader->getSheetIterator() as $sheet) {
            foreach ($sheet->getRowIterator() as $row) {
                $cells = $row->toArray();
                while ($cells !== [] && end($cells) === '') {
                    array_pop($cells);
                }
                $rows[] = $cells;
            }
        }
        $reader->close();

        return $rows;
    }
}
