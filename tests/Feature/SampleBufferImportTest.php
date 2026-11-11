<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\ProductGroup;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class SampleBufferImportTest extends TestCase
{
    use RefreshDatabase;

    private const HEADER = 'Datum;Projectnaam;Monsternaam;Details;THT;THT datum;Analyses;Opmerkingen;Locatie';

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get(route('sample-buffers.import'))->assertRedirect(route('login'));
        $this->get(route('sample-buffers.import.template'))->assertRedirect(route('login'));
        $this->postJson(route('sample-buffers.import.store'))->assertUnauthorized();
    }

    public function test_users_without_import_permission_get_403_everywhere(): void
    {
        $this->actingAs(User::factory()->create(['enabled' => 1]));
        Gate::define('clients.import', fn (): bool => false);
        $client = $this->client();

        $this->get(route('sample-buffers.import'))->assertForbidden();
        $this->get(route('sample-buffers.import.template'))->assertForbidden();
        $this->getJson(route('sample-buffers.import.clients', ['query' => 'Zie']))->assertForbidden();
        $this->postJson(route('sample-buffers.import.preview'), ['client' => $client->id, 'file' => $this->csv("20-09-2026;P;M;D;nee;;;;L\n")])->assertForbidden();
        $this->postJson(route('sample-buffers.import.store'), $this->payload($client))->assertForbidden();
        $this->assertDatabaseCount('samplebuffers', 0);
    }

    public function test_import_page_and_navigation_link_are_shown_with_import_permission(): void
    {
        $this->actingAs(User::factory()->create(['enabled' => 1]));
        Gate::define('clients.import', fn (): bool => true);

        $this->get(route('sample-buffers.import'))
            ->assertOk()
            ->assertSee('<sample-buffer-import', false)
            ->assertSee('href="'.route('sample-buffers.import').'"', false);
    }

    public function test_import_template_can_be_downloaded_with_import_permission(): void
    {
        $this->allowImport();

        $response = $this->get(route('sample-buffers.import.template'))->assertOk();

        $this->assertStringContainsString('attachment; filename=maz_bulk.xlsm', $response->headers->get('content-disposition'));
        $this->assertFileEquals(resource_path('files/maz_bulk.xlsm'), $response->getFile()->getPathname());
    }

    public function test_client_search_returns_active_clients_matching_the_query(): void
    {
        $this->allowImport();
        $this->client('Ziekenhuis Zoë');
        $this->client('Ziekenhuis Inactief', active: 0);

        $this->getJson(route('sample-buffers.import.clients', ['query' => 'ziekenhuis']))
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Ziekenhuis Zoë');
    }

    public function test_preview_decodes_a_windows_1252_file_with_dutch_diacritics(): void
    {
        $this->allowImport();
        $client = $this->client();
        $contents = mb_convert_encoding(self::HEADER."\n20-09-2026;Café Zoë;Kraan één;Keuken;ja;25-12-2026;Legionella;Koel;Gebouw 2\n", 'Windows-1252', 'UTF-8');

        $this->postJson(route('sample-buffers.import.preview'), ['client' => $client->id, 'file' => $this->csv($contents)])
            ->assertOk()
            ->assertJsonPath('data.encoding', 'Windows-1252')
            ->assertJsonPath('data.separator', 'semicolon')
            ->assertJsonPath('data.meta_columns', ['Locatie'])
            ->assertJsonPath('data.rows.0.project_name', 'Café Zoë')
            ->assertJsonPath('data.rows.0.sample_name', 'Kraan één')
            ->assertJsonPath('data.rows.0.sampling_date', '2026-09-20')
            ->assertJsonPath('data.rows.0.tht', true)
            ->assertJsonPath('data.rows.0.tht_date', '2026-12-25')
            ->assertJsonPath('data.rows.0.meta', ['Gebouw 2']);
        $this->assertDatabaseCount('samplebuffers', 0);
    }

    public function test_preview_honours_the_chosen_separator(): void
    {
        $this->allowImport();
        $client = $this->client();
        $contents = str_replace(';', "\t", self::HEADER."\n20-09-2026;P;M;D;nee;;;;L\n");

        $this->postJson(route('sample-buffers.import.preview'), ['client' => $client->id, 'file' => $this->csv($contents), 'separator' => 'tab'])
            ->assertOk()
            ->assertJsonPath('data.separator', 'tab')
            ->assertJsonPath('data.rows.0.sample_name', 'M');
    }

    public function test_preview_rejects_a_file_that_is_not_a_csv_with_422(): void
    {
        $this->allowImport();

        $this->postJson(route('sample-buffers.import.preview'), [
            'client' => $this->client()->id,
            'file' => UploadedFile::fake()->createWithContent('werkblad.xlsx', 'PK'),
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['file' => 'Alleen CSV-bestanden worden ondersteund. Sla het werkblad in Excel op als CSV.']);
    }

    public function test_preview_rejects_a_file_with_too_few_columns_with_422(): void
    {
        $this->allowImport();

        $this->postJson(route('sample-buffers.import.preview'), [
            'client' => $this->client()->id,
            'file' => $this->csv("Datum;Project\n20-09-2026;P\n"),
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrorFor('file');
    }

    public function test_preview_rejects_an_inactive_client_with_422(): void
    {
        $this->allowImport();

        $this->postJson(route('sample-buffers.import.preview'), [
            'client' => $this->client(active: 0)->id,
            'file' => $this->csv(self::HEADER."\n20-09-2026;P;M;D;nee;;;;L\n"),
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['client' => 'Deze klant bestaat niet of is niet actief.']);
    }

    public function test_commit_stores_the_reviewed_rows_in_the_buffer(): void
    {
        $this->allowImport();
        $client = $this->client();
        $this->productGroup($client, 11);

        $this->postJson(route('sample-buffers.import.store'), $this->payload($client, [
            'sample_name' => 'Kraan één',
            'product_group' => 11,
            'meta' => ['Gebouw 2'],
        ]))
            ->assertCreated()
            ->assertJsonPath('data.count', 1)
            ->assertJsonPath('data.portal_url', null);

        $this->assertDatabaseHas('samplebuffers', [
            'client' => $client->id,
            'source' => 1,
            'sample_name' => 'Kraan één',
            'sampling_date' => '20-09-2026',
            'portal_product_group_id' => 11,
            'authorized' => 0,
        ]);
    }

    public function test_commit_links_to_the_portal_only_for_users_with_portal_access(): void
    {
        $this->allowImport();
        Gate::define('portal.access', fn (): bool => true);

        $this->postJson(route('sample-buffers.import.store'), $this->payload($this->client()))
            ->assertCreated()
            ->assertJsonPath('data.portal_url', route('sample-buffers.index'));
    }

    public function test_commit_rejects_invalid_rows_with_per_row_422_errors_and_stores_nothing(): void
    {
        $this->allowImport();
        $client = $this->client();
        $this->productGroup($this->client('Andere klant'), 99);

        $this->postJson(route('sample-buffers.import.store'), [
            'client' => $client->id,
            'meta_columns' => [],
            'rows' => [
                $this->row(),
                $this->row(['sampling_date' => '']),
                $this->row(['tht' => true, 'tht_date' => null]),
                $this->row(['project_name' => '', 'sample_name' => '', 'sample_details' => '']),
                $this->row(['product_group' => 99]),
            ],
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'rows.1.sampling_date' => 'Vul een geldige monsternamedatum in.',
                'rows.2.tht_date' => 'Vul de THT-datum in voor een THT-onderzoek.',
                'rows.3.sample_name' => 'Vul minimaal een projectnaam, monsternaam of details in.',
                'rows.4.product_group' => 'Deze productgroep hoort niet bij de gekozen klant.',
            ])
            ->assertJsonMissingValidationErrors(['rows.0.sampling_date']);
        $this->assertDatabaseCount('samplebuffers', 0);
    }

    public function test_commit_rejects_an_empty_import_with_422(): void
    {
        $this->allowImport();

        $this->postJson(route('sample-buffers.import.store'), ['client' => $this->client()->id, 'rows' => []])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['rows' => 'Er zijn geen rijen om te importeren.']);
    }

    public function test_commit_rejects_an_inactive_client_with_422(): void
    {
        $this->allowImport();

        $this->postJson(route('sample-buffers.import.store'), $this->payload($this->client(active: 0)))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['client' => 'Deze klant bestaat niet of is niet actief.']);
        $this->assertDatabaseCount('samplebuffers', 0);
    }

    private function allowImport(): void
    {
        $this->actingAs(User::factory()->create(['enabled' => 1]));
        Gate::define('clients.import', fn (): bool => true);
    }

    private function client(string $name = 'Ziekenhuis Zoë', int $active = 1): Client
    {
        return Client::create(['name' => $name, 'active' => $active]);
    }

    private function productGroup(Client $client, int $portalId): void
    {
        ProductGroup::create(['client_id' => $client->id, 'portal_id' => $portalId, 'name' => 'Drinkwater', 'default' => 0, 'visible' => 1]);
    }

    private function csv(string $contents): UploadedFile
    {
        return UploadedFile::fake()->createWithContent('import.csv', $contents);
    }

    /**
     * @param  array<string, mixed>  $rowOverrides
     * @return array<string, mixed>
     */
    private function payload(Client $client, array $rowOverrides = []): array
    {
        return [
            'client' => $client->id,
            'meta_columns' => ['Locatie'],
            'rows' => [$this->row($rowOverrides)],
        ];
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function row(array $overrides = []): array
    {
        return [
            'sampling_date' => '2026-09-20',
            'project_name' => 'Project A',
            'sample_name' => 'Kraan 1',
            'sample_details' => 'Keuken',
            'tht' => false,
            'tht_date' => null,
            'analyses_selected' => '',
            'misc_directions' => '',
            'product_group' => null,
            'meta' => ['Gebouw 2'],
            ...$overrides,
        ];
    }
}
