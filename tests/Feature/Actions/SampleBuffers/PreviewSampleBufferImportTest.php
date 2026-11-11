<?php

namespace Tests\Feature\Actions\SampleBuffers;

use App\Actions\SampleBuffers\PreviewSampleBufferImport;
use App\Models\Client;
use App\Models\Cvar;
use App\Models\ProductGroup;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class PreviewSampleBufferImportTest extends TestCase
{
    use RefreshDatabase;

    private const HEADER = ['Datum', 'Project', 'Monster', 'Details', 'THT', 'THT datum', 'Analyses', 'Overige'];

    public function test_maps_positional_columns_and_collects_the_remaining_columns_as_metadata(): void
    {
        $client = $this->client();

        $preview = $this->preview($client, [['20-09-2026', 'Project A', 'Kraan 1', 'Keuken', 'nee', '', 'Legionella', 'Koel houden', 'Gebouw 2', 'Reservoir', 'genegeerd', 'Tapkraan']], ['Locatie', '_ProductGroep', '', 'Punt']);

        $this->assertSame(['Locatie', 'Punt'], $preview['meta_columns']);
        $this->assertSame(['id' => $client->id, 'name' => 'Ziekenhuis Zoë'], $preview['client']);
        $this->assertSame('semicolon', $preview['separator']);
        $this->assertSame('UTF-8', $preview['encoding']);
        $this->assertSame([
            'line' => 2,
            'sampling_date' => '2026-09-20',
            'sampling_date_source' => '20-09-2026',
            'project_name' => 'Project A',
            'sample_name' => 'Kraan 1',
            'sample_details' => 'Keuken',
            'tht' => false,
            'tht_date' => '',
            'tht_date_source' => '',
            'analyses_selected' => 'Legionella',
            'misc_directions' => 'Koel houden',
            'product_group' => null,
            'product_group_text' => 'Reservoir',
            'product_group_alert' => true,
            'meta' => ['Gebouw 2', 'Tapkraan'],
        ], $preview['rows'][0]);
    }

    /** @return array<string, array{string, string}> */
    public static function recognisedDates(): array
    {
        return [
            'day-month-year' => ['20-09-2026', '2026-09-20'],
            'slashes' => ['20/09/2026', '2026-09-20'],
            'dots' => ['20.09.2026', '2026-09-20'],
            'iso' => ['2026-09-20', '2026-09-20'],
            'single digits and two digit year' => ['5-9-26', '2026-09-05'],
            'with a time' => ['20-09-2026 14:30', '2026-09-20'],
            'abbreviated month' => ['20 sep 2026', '2026-09-20'],
            'dutch month with a different english spelling' => ['5 maart 2026', '2026-03-05'],
            'dutch may' => ['5 mei 2026', '2026-05-05'],
        ];
    }

    #[DataProvider('recognisedDates')]
    public function test_recognises_the_date_notations_customers_use(string $written, string $expected): void
    {
        $preview = $this->preview($this->client(), [[$written, 'Project', 'Monster', '', 'nee', '', '', '']]);

        $this->assertSame($expected, $preview['rows'][0]['sampling_date']);
        $this->assertSame($written, $preview['rows'][0]['sampling_date_source']);
    }

    /** @return array<string, array{string}> */
    public static function unreadableDates(): array
    {
        return [
            'day that does not exist' => ['31-02-2026'],
            'month that does not exist' => ['2026-13-01'],
            'text' => ['binnenkort'],
            'bare year' => ['2026'],
            'empty' => [''],
        ];
    }

    #[DataProvider('unreadableDates')]
    public function test_leaves_unreadable_dates_empty_so_the_user_has_to_fix_them(string $written): void
    {
        $preview = $this->preview($this->client(), [[$written, 'Project', 'Monster', '', 'nee', '', '', '']]);

        $this->assertSame('', $preview['rows'][0]['sampling_date']);
        $this->assertSame($written, $preview['rows'][0]['sampling_date_source']);
    }

    /** @return array<string, array{string, bool}> */
    public static function thtValues(): array
    {
        return [
            'ja' => ['ja', true],
            'J' => ['J', true],
            'y' => ['y', true],
            'YES' => ['YES', true],
            'one' => ['1', true],
            'excel true' => ['WAAR', true],
            'english true' => ['TRUE', true],
            'nee' => ['nee', false],
            'zero' => ['0', false],
            'empty' => ['', false],
            'unrelated text' => ['misschien', false],
        ];
    }

    #[DataProvider('thtValues')]
    public function test_reads_the_tht_flag_like_the_legacy_importer(string $written, bool $expected): void
    {
        $preview = $this->preview($this->client(), [['20-09-2026', 'Project', 'Monster', '', $written, '25/12/2026', '', '']]);

        $this->assertSame($expected, $preview['rows'][0]['tht']);
        $this->assertSame($expected ? '2026-12-25' : '', $preview['rows'][0]['tht_date']);
    }

    public function test_a_tht_row_without_a_readable_tht_date_keeps_the_original_text_for_the_user_to_fix(): void
    {
        $preview = $this->preview($this->client(), [['20-09-2026', 'Project', 'Monster', '', 'ja', 'volgende week', '', '']]);

        $this->assertSame('', $preview['rows'][0]['tht_date']);
        $this->assertSame('volgende week', $preview['rows'][0]['tht_date_source']);
    }

    public function test_skips_rows_without_project_sample_and_details_and_says_so(): void
    {
        $preview = $this->preview($this->client(), [
            ['20-09-2026', '', '', '', 'nee', '', 'Legionella', ''],
            ['20-09-2026', '', 'Monster', '', 'nee', '', '', ''],
            ['20-09-2026', '', '', 'Alleen details', 'nee', '', '', ''],
        ]);

        $this->assertSame([3, 4], array_column($preview['rows'], 'line'));
        $this->assertSame(['1 rij(en) zonder projectnaam, monsternaam en details zijn overgeslagen (rij 2).'], $preview['warnings']);
    }

    public function test_warns_about_duplicate_metadata_columns_and_keeps_the_last_one(): void
    {
        $preview = $this->preview($this->client(), [['20-09-2026', 'Project', 'Monster', '', 'nee', '', '', '', 'eerste', 'tweede']], ['Locatie', 'Locatie']);

        $this->assertSame(['Locatie'], $preview['meta_columns']);
        $this->assertSame(['tweede'], $preview['rows'][0]['meta']);
        $this->assertSame(['De kolom "Locatie" komt meerdere keren voor; alleen de laatste kolom wordt gebruikt.'], $preview['warnings']);
    }

    public function test_selects_the_client_default_product_group_without_alert_when_nothing_was_supplied(): void
    {
        $client = $this->client();
        $this->productGroup($client, 11, 'Drinkwater');
        $this->productGroup($client, 12, 'Zwemwater', default: true);

        $preview = $this->preview($client, [['20-09-2026', 'Project', 'Monster', '', 'nee', '', '', '']], ['_ProductGroep']);

        $this->assertSame(12, $preview['rows'][0]['product_group']);
        $this->assertFalse($preview['rows'][0]['product_group_alert']);
        $this->assertSame([['id' => 11, 'name' => 'Drinkwater', 'active' => true], ['id' => 12, 'name' => 'Zwemwater', 'active' => true]], $preview['product_groups']);
    }

    public function test_matches_the_product_group_column_to_a_client_product_group_ignoring_case_and_diacritics(): void
    {
        $client = $this->client();
        $this->productGroup($client, 11, 'Drinkwater', default: true);
        $this->productGroup($client, 12, 'Café water');

        $preview = $this->preview($client, [['20-09-2026', 'Project', 'Monster', '', 'nee', '', '', '', 'cafe WATER']], ['_productgroep']);

        $this->assertSame(12, $preview['rows'][0]['product_group']);
        $this->assertFalse($preview['rows'][0]['product_group_alert']);
    }

    public function test_alerts_and_falls_back_to_the_default_when_the_product_group_column_matches_nothing(): void
    {
        $client = $this->client();
        $this->productGroup($client, 11, 'Drinkwater', default: true);

        $preview = $this->preview($client, [['20-09-2026', 'Project', 'Monster', '', 'nee', '', '', '', 'Afvalwater']], ['_ProductGroep']);

        $this->assertSame(11, $preview['rows'][0]['product_group']);
        $this->assertTrue($preview['rows'][0]['product_group_alert']);
        $this->assertSame('Afvalwater', $preview['rows'][0]['product_group_text']);
    }

    public function test_assay_column_clients_supply_the_product_group_in_the_analyses_column(): void
    {
        $client = $this->client();
        $this->usesAssayColumn([$client->id]);
        $this->productGroup($client, 11, 'Drinkwater', default: true);
        $this->productGroup($client, 12, 'Zwemwater');

        $preview = $this->preview($client, [['20-09-2026', 'Project', 'Monster', '', 'nee', '', 'Zwemwater', '', 'Drinkwater']], ['_ProductGroep']);

        $this->assertTrue($preview['uses_assay_column']);
        $this->assertSame(12, $preview['rows'][0]['product_group']);
        $this->assertFalse($preview['rows'][0]['product_group_alert']);
        $this->assertSame('Zwemwater', $preview['rows'][0]['analyses_selected']);
    }

    /** @return array<string, array{string}> */
    public static function unusableAnalysesColumns(): array
    {
        return ['unknown group' => ['Afvalwater'], 'empty' => ['']];
    }

    #[DataProvider('unusableAnalysesColumns')]
    public function test_assay_column_clients_always_get_an_alert_when_the_analyses_column_gives_no_product_group(string $analyses): void
    {
        $client = $this->client();
        $this->usesAssayColumn([$client->id]);
        $this->productGroup($client, 11, 'Drinkwater', default: true);

        $preview = $this->preview($client, [['20-09-2026', 'Project', 'Monster', '', 'nee', '', $analyses, '']]);

        $this->assertSame(11, $preview['rows'][0]['product_group']);
        $this->assertTrue($preview['rows'][0]['product_group_alert']);
    }

    public function test_other_clients_do_not_use_the_analyses_column_for_product_groups(): void
    {
        $client = $this->client();
        $this->usesAssayColumn([$client->id + 1]);
        $this->productGroup($client, 11, 'Drinkwater', default: true);
        $this->productGroup($client, 12, 'Zwemwater');

        $preview = $this->preview($client, [['20-09-2026', 'Project', 'Monster', '', 'nee', '', 'Zwemwater', '']]);

        $this->assertFalse($preview['uses_assay_column']);
        $this->assertSame(11, $preview['rows'][0]['product_group']);
        $this->assertFalse($preview['rows'][0]['product_group_alert']);
    }

    public function test_inactive_product_groups_are_offered_but_never_matched_automatically(): void
    {
        $client = $this->client();
        $this->productGroup($client, 11, 'Drinkwater', default: true);
        $this->productGroup($client, 12, 'Zwemwater', visible: false);

        $preview = $this->preview($client, [['20-09-2026', 'Project', 'Monster', '', 'nee', '', '', '', 'Zwemwater']], ['_ProductGroep']);

        $this->assertSame(11, $preview['rows'][0]['product_group']);
        $this->assertTrue($preview['rows'][0]['product_group_alert']);
        $this->assertFalse($preview['product_groups'][1]['active']);
    }

    public function test_leaves_the_product_group_empty_when_the_client_has_no_default(): void
    {
        $client = $this->client();
        $this->productGroup($client, 11, 'Drinkwater');

        $preview = $this->preview($client, [['20-09-2026', 'Project', 'Monster', '', 'nee', '', '', '']]);

        $this->assertNull($preview['rows'][0]['product_group']);
    }

    private function client(): Client
    {
        return Client::create(['name' => 'Ziekenhuis Zoë', 'active' => 1]);
    }

    private function productGroup(Client $client, int $portalId, string $name, bool $default = false, bool $visible = true): void
    {
        ProductGroup::create(['client_id' => $client->id, 'portal_id' => $portalId, 'name' => $name, 'default' => (int) $default, 'visible' => (int) $visible]);
    }

    /**
     * @param  list<int>  $clientIds
     */
    private function usesAssayColumn(array $clientIds): void
    {
        Cvar::create(['cvar' => 'CLIENT_USES_ASSAY_COLUMN_IMPORT', 'value' => json_encode($clientIds), 'default' => '{}', 'description' => '']);
    }

    /**
     * @param  list<list<string>>  $rows
     * @param  list<string>  $extraHeaders
     * @return array<string, mixed>
     */
    private function preview(Client $client, array $rows, array $extraHeaders = []): array
    {
        $contents = implode("\n", array_map(fn (array $row): string => implode(';', $row), [[...self::HEADER, ...$extraHeaders], ...$rows]))."\n";

        return app(PreviewSampleBufferImport::class)->handle($client, $contents);
    }
}
