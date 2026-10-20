<?php

namespace Tests\Feature;

use App\Models\Assay;
use App\Models\AssayProfile;
use App\Models\Client;
use App\Models\LabelDesign;
use App\Models\LabelEvent;
use App\Models\Printer;
use App\Models\ReferenceSource;
use App\Models\ResearchProfile;
use Closure;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ImportLegacyCsvDirectoryCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_printing_exports_replace_existing_records_preserve_legacy_data_and_reset_id_sequences(): void
    {
        LabelEvent::factory()->create();
        $zpl = "^XA\n^FD\"{sample_barcode}\"^FS\n^XZ";
        $eventData = '{"type":"assayIs","inspect_value":"1419","negative":"0"}';
        $exports = [
            'printers.csv' => [
                ['id', 'name', 'adres', 'port', 'type'],
                [41, 'Monsterbalie', '127.0.0.1', 9100, 1],
                [72, 'Platen', 'zebra.lab.local', 9100, 0],
            ],
            'labeldesigns.csv' => [
                ['id', 'name', 'zpl', 'default_sample', 'default_analysis', 'default_printer'],
                [103, 'Monsteretiket', $zpl, 1, 0, 41],
            ],
            'labelevents.csv' => [
                ['id', 'event', 'event_group', 'event_data', 'print_label', 'print_to', 'copies'],
                [207, 'L', 2, $eventData, 103, 72, 0],
            ],
        ];
        $directory = sys_get_temp_dir().DIRECTORY_SEPARATOR.uniqid('mesa-legacy-csv-');
        mkdir($directory);

        try {
            foreach ($exports as $filename => $rows) {
                $this->writeCsv($directory.DIRECTORY_SEPARATOR.$filename, $rows);
            }

            $this->artisan('legacy:import-csv', ['directory' => $directory])
                ->expectsOutput('Imported 4 row(s) from 3 file(s).')
                ->assertSuccessful();
        } finally {
            foreach (array_keys($exports) as $filename) {
                $path = $directory.DIRECTORY_SEPARATOR.$filename;

                if (is_file($path)) {
                    unlink($path);
                }
            }

            rmdir($directory);
        }

        $this->assertDatabaseCount('printers', 2);
        $this->assertDatabaseCount('labeldesigns', 1);
        $this->assertDatabaseCount('labelevents', 1);
        $this->assertDatabaseHas('printers', ['id' => 41, 'name' => 'Monsterbalie', 'adres' => '127.0.0.1', 'port' => 9100, 'type' => 1]);
        $this->assertDatabaseHas('printers', ['id' => 72, 'name' => 'Platen', 'adres' => 'zebra.lab.local', 'port' => 9100, 'type' => 0]);

        $design = LabelDesign::query()->findOrFail(103);
        $this->assertSame('Monsteretiket', $design->name);
        $this->assertSame($zpl, $design->zpl);
        $this->assertTrue($design->default_sample);
        $this->assertFalse($design->default_analysis);
        $this->assertSame(41, $design->printer->id);

        $event = LabelEvent::query()->findOrFail(207);
        $this->assertSame('L', $event->event);
        $this->assertSame(2, $event->event_group);
        $this->assertSame($eventData, $event->event_data);
        $this->assertSame(0, $event->copies);
        $this->assertTrue($event->labelDesign->is($design));
        $this->assertSame(72, $event->printer->id);

        $newPrinter = Printer::factory()->create();
        $newDesign = LabelDesign::factory()->for($newPrinter, 'printer')->create();
        $newEvent = LabelEvent::factory()->for($newPrinter, 'printer')->for($newDesign, 'labelDesign')->create();

        $this->assertGreaterThan(72, $newPrinter->id);
        $this->assertGreaterThan(103, $newDesign->id);
        $this->assertGreaterThan(207, $newEvent->id);
    }

    public function test_profile_exports_preserve_json_settings_ids_revisions_and_reference_sources(): void
    {
        $exports = [
            'assays.csv' => [
                ['id', 'original_id', 'name', 'type_base', 'media_id', 'dillution', 'replicates', 'confirmation', 'confirmation_script', 'type', 'meta_assays', 'max_count', 'min_count', 'script', 'custom_fields', 'duration', 'start_from', 'article_code'],
                [422, 422, 'Plate count', 1, '[]', 1, 1, 0, '{}', 1, '', 300, 10, 'file:maz7218', '{"method":"ISO"}', '2', 'r', ''],
            ],
            'referencesources.csv' => [
                ['id', 'name', 'client'],
                [7, '{"nl":"Wettelijke bron","en":"Legal source"}', 'NA'],
            ],
            'researchprofiles.csv' => [
                ['id', 'original_id', 'name', 'global', 'client', 'active', 'portal_visible', 'lims_visible'],
                [21, 21, 'Original profile', 1, 0, 0, 1, 0],
                [23, 21, 'Current profile', 1, 0, 1, 1, 1],
            ],
            'assayprofiles.csv' => [
                ['id', 'research_profile', 'assay', 'dillutions', 'replicates', 'reference', 'hidden', 'project_order', 'conf_trip', 'reference_source'],
                [119, 21, 422, '{"-1":"0.1"}', 0, '{"ref_kve":"100"}', 1, 1, 0, 'NA'],
                [6930, 23, 422, '{"-2":"0.01","-3":"0.001"}', 2, '{"ref_kve":"0","ref_other":""}', 0, 3, 50, 7],
            ],
        ];

        $this->withCsvExports($exports, function (string $directory): void {
            $this->artisan('legacy:import-csv', ['directory' => $directory])
                ->expectsOutput('Imported 6 row(s) from 4 file(s).')
                ->assertSuccessful();
        });

        $current = AssayProfile::query()->findOrFail(6930);
        $this->assertSame(['-2' => '0.01', '-3' => '0.001'], $current->dillutions);
        $this->assertSame(['ref_kve' => '0', 'ref_other' => ''], $current->reference);
        $this->assertSame(23, $current->researchProfile->id);
        $this->assertSame(21, $current->researchProfile->original_id);
        $this->assertTrue($current->researchProfile->active);
        $this->assertTrue($current->researchProfile->portal_visible);
        $this->assertTrue($current->researchProfile->lims_visible);
        $this->assertSame(422, $current->assayRecord->id);
        $this->assertSame(7, $current->referenceSource->id);
        $this->assertSame(['nl' => 'Wettelijke bron', 'en' => 'Legal source'], $current->referenceSource->name);
        $this->assertNull($current->referenceSource->client);
        $this->assertSame(2, $current->replicates);
        $this->assertSame(3, $current->project_order);
        $this->assertSame(50, $current->conf_trip);
        $this->assertFalse($current->hidden);
        $this->assertDatabaseHas('assayprofiles', [
            'id' => 6930, 'dillutions' => '{"-2":"0.01","-3":"0.001"}',
            'reference' => '{"ref_kve":"0","ref_other":""}',
        ]);

        $original = AssayProfile::query()->findOrFail(119);
        $this->assertSame(['-1' => '0.1'], $original->dillutions);
        $this->assertSame(['ref_kve' => '100'], $original->reference);
        $this->assertNull($original->reference_source);
        $this->assertTrue($original->hidden);
        $this->assertFalse(ResearchProfile::query()->findOrFail(21)->active);
        $this->assertSame('{"method":"ISO"}', Assay::query()->findOrFail(422)->custom_fields);
        $this->assertSame([21, 23], ResearchProfile::query()->orderBy('id')->pluck('id')->all());
        $this->assertDatabaseCount('referencesources', 1);
        $this->assertSame('Wettelijke bron', ReferenceSource::query()->findOrFail(7)->localizedName());
    }

    #[DataProvider('largeProfileExports')]
    public function test_large_profile_exports_use_bounded_bulk_inserts_and_preserve_json_settings(int $rowCount, array $expectedBatchSizes): void
    {
        $rows = [['id', 'research_profile', 'assay', 'dillutions', 'reference']];

        for ($id = 1; $id <= $rowCount; $id++) {
            $rows[] = [$id, 23, 422, '{"-2":"0.01"}', json_encode(['ref_kve' => (string) ($id - 1)], JSON_THROW_ON_ERROR)];
        }

        $batchSizes = [];
        DB::listen(function (QueryExecuted $query) use (&$batchSizes): void {
            if (str_starts_with(strtolower($query->sql), 'insert into') && str_contains($query->sql, 'assayprofiles')) {
                $batchSizes[] = intdiv(count($query->bindings), 5);
            }
        });
        Event::fake(['eloquent.creating: '.AssayProfile::class]);

        $this->withCsvExports(['assayprofiles.csv' => $rows], function (string $directory) use ($rowCount): void {
            $this->artisan('legacy:import-csv', ['directory' => $directory])
                ->expectsOutput("Imported {$rowCount} row(s) from 1 file(s).")
                ->assertSuccessful();
        });

        $this->assertCount(count($expectedBatchSizes), $batchSizes);
        $this->assertSame($expectedBatchSizes, $batchSizes);
        $this->assertDatabaseCount('assayprofiles', $rowCount);
        $this->assertSame(['ref_kve' => '0'], AssayProfile::query()->findOrFail(1)->reference);
        $last = AssayProfile::query()->findOrFail($rowCount);
        $this->assertSame(['-2' => '0.01'], $last->dillutions);
        $this->assertSame(['ref_kve' => (string) ($rowCount - 1)], $last->reference);
        Event::assertNotDispatched('eloquent.creating: '.AssayProfile::class);
    }

    public static function largeProfileExports(): array
    {
        return [
            'exactly one full batch' => [500, [500]],
            'full batches and a final partial batch' => [1001, [500, 500, 1]],
        ];
    }

    public function test_header_only_export_finishes_without_attempting_an_empty_batch(): void
    {
        $this->withCsvExports(['assayprofiles.csv' => [
            ['id', 'research_profile', 'assay', 'dillutions', 'reference'],
        ]], function (string $directory): void {
            $this->artisan('legacy:import-csv', ['directory' => $directory])
                ->expectsOutput('assayprofiles.csv: 0 imported, 0 failed.')
                ->expectsOutput('Imported 0 row(s) from 1 file(s).')
                ->assertSuccessful();
        });

        $this->assertDatabaseCount('assayprofiles', 0);
    }

    public function test_failed_batch_reports_the_bad_row_imports_good_rows_and_continues_to_other_files(): void
    {
        $exports = [
            'printers.csv' => [
                ['id', 'name', 'adres', 'port', 'type'],
                [41, 'First', '127.0.0.1', 9100, 1],
                [41, 'Duplicate', '127.0.0.2', 9100, 1],
                [72, 'Second', '127.0.0.3', 9100, 0],
                [103, 'Third', '127.0.0.4', 9100, 0],
            ],
            'labeldesigns.csv' => [
                ['id', 'name', 'zpl', 'default_printer'],
                [207, 'Label', '^XA^FD{sample_barcode}^FS^XZ', 72],
            ],
        ];
        Event::fake(['eloquent.creating: '.Printer::class]);

        $this->withCsvExports($exports, function (string $directory): void {
            $this->artisan('legacy:import-csv', ['directory' => $directory])
                ->expectsOutputToContain('printers.csv: line 3:')
                ->expectsOutput('printers.csv: 3 imported, 1 failed.')
                ->expectsOutput('Imported 4 row(s) from 2 file(s).')
                ->expectsOutput('1 row(s) or file(s) failed to import.')
                ->assertExitCode(1);
        });

        $this->assertSame([41, 72, 103], Printer::query()->orderBy('id')->pluck('id')->all());
        $this->assertDatabaseHas('printers', ['id' => 41, 'name' => 'First']);
        $this->assertSame(72, LabelDesign::query()->findOrFail(207)->printer->id);
        Event::assertNotDispatched('eloquent.creating: '.Printer::class);
        $this->assertGreaterThan(103, Printer::factory()->create()->id);
    }

    public function test_failure_in_a_later_batch_does_not_undo_previous_batches_or_overwrite_existing_ids(): void
    {
        $rows = [['id', 'research_profile', 'assay', 'dillutions', 'reference']];

        for ($id = 1; $id <= 500; $id++) {
            $rows[] = [$id, 23, 422, '{"-1":"0.1"}', '{"ref_kve":"0"}'];
        }

        $rows[] = [501, 23, 422, '{"-2":"0.01"}', '{"ref_kve":"1"}'];
        $rows[] = [1, 23, 422, '{"-3":"0.001"}', '{"ref_kve":"999"}'];
        $rows[] = [502, 23, 422, '{"-2":"0.01"}', '{"ref_kve":"2"}'];

        $this->withCsvExports(['assayprofiles.csv' => $rows], function (string $directory): void {
            $this->artisan('legacy:import-csv', ['directory' => $directory])
                ->expectsOutputToContain('assayprofiles.csv: line 503:')
                ->expectsOutput('assayprofiles.csv: 502 imported, 1 failed.')
                ->expectsOutput('Imported 502 row(s) from 1 file(s).')
                ->assertExitCode(1);
        });

        $this->assertDatabaseCount('assayprofiles', 502);
        $this->assertSame(['ref_kve' => '0'], AssayProfile::query()->findOrFail(1)->reference);
        $this->assertSame(['ref_kve' => '1'], AssayProfile::query()->findOrFail(501)->reference);
        $this->assertSame(['ref_kve' => '2'], AssayProfile::query()->findOrFail(502)->reference);
    }

    public function test_bulk_client_import_keeps_ids_and_company_data_but_anonymizes_personal_data(): void
    {
        $exports = ['clients.csv' => [
            ['id', 'reference', 'name', 'fname', 'email'],
            [41, 'AC001', 'Legacy Company A', 'Original first A', 'old-a@example.test'],
            [72, 'AC002', 'Legacy Company B', 'Original first B', 'old-b@example.test'],
        ]];

        $this->withCsvExports($exports, function (string $directory): void {
            $this->artisan('legacy:import-csv', ['directory' => $directory])
                ->expectsOutput('Imported 2 row(s) from 1 file(s).')
                ->assertSuccessful();
        });

        $first = Client::query()->findOrFail(41);
        $this->assertSame('AC001', $first->reference);
        $this->assertSame('Legacy Company A', $first->name);
        $this->assertNotEmpty($first->fname);
        $this->assertNotSame('Original first A', $first->fname);
        $this->assertNotSame('old-a@example.test', $first->email);
        $second = Client::query()->findOrFail(72);
        $this->assertSame('AC002', $second->reference);
        $this->assertSame('Legacy Company B', $second->name);
        $this->assertNotEmpty($second->fname);
        $this->assertNotSame('Original first B', $second->fname);
        $this->assertNotSame('old-b@example.test', $second->email);
    }

    #[DataProvider('legacyProfileJsonFormats')]
    public function test_legacy_profile_json_formats_are_stored_as_single_json_objects(
        string $dillutions,
        string $reference,
        array $expectedDillutions,
        array $expectedReference,
    ): void {
        $exports = ['assayprofiles.csv' => [
            ['id', 'research_profile', 'assay', 'dillutions', 'reference'],
            [6930, 23, 422, $dillutions, $reference],
        ]];

        $this->withCsvExports($exports, function (string $directory): void {
            $this->artisan('legacy:import-csv', ['directory' => $directory])
                ->expectsOutput('Imported 1 row(s) from 1 file(s).')
                ->assertSuccessful();
        });

        $profile = AssayProfile::query()->findOrFail(6930);
        $this->assertSame($expectedDillutions, $profile->dillutions);
        $this->assertSame($expectedReference, $profile->reference);
        $this->assertSame($expectedDillutions, json_decode($profile->getRawOriginal('dillutions'), true, flags: JSON_THROW_ON_ERROR));
        $this->assertSame($expectedReference, json_decode($profile->getRawOriginal('reference'), true, flags: JSON_THROW_ON_ERROR));
        $this->assertStringStartsWith('{', $profile->getRawOriginal('dillutions'));
        $this->assertStringStartsWith('{', $profile->getRawOriginal('reference'));
    }

    public static function legacyProfileJsonFormats(): array
    {
        return [
            'double-encoded legacy JSON' => [
                json_encode('{"-2":"0.01"}', JSON_THROW_ON_ERROR),
                json_encode('{"ref_kve":"0","ref_presence":"Afwezig"}', JSON_THROW_ON_ERROR),
                ['-2' => '0.01'], ['ref_kve' => '0', 'ref_presence' => 'Afwezig'],
            ],
            'indexed legacy arrays' => [
                '[0.1,0.01]', '["Afwezig","Aanwezig"]',
                [0.1, 0.01], ['Afwezig', 'Aanwezig'],
            ],
            'empty objects' => ['{}', '{}', [], []],
            'empty arrays' => ['[]', '[]', [], []],
            'blank CSV cells' => ['', '', [], []],
            'legacy null markers' => ['NA', 'NULL', [], []],
            'JSON null' => ['null', 'null', [], []],
            'JSON-encoded empty strings' => ['""', '""', [], []],
        ];
    }

    #[DataProvider('invalidProfileJson')]
    public function test_invalid_profile_json_is_reported_per_row_without_saving_empty_settings(
        string $attribute,
        string $value,
        string $error,
    ): void {
        $invalid = ['dillutions' => '{"-2":"0.01"}', 'reference' => '{"ref_kve":"0"}'];
        $invalid[$attribute] = $value;
        $exports = ['assayprofiles.csv' => [
            ['id', 'research_profile', 'assay', 'dillutions', 'reference'],
            [119, 21, 422, '{"-1":"0.1"}', '{"ref_kve":"100"}'],
            [6930, 23, 422, $invalid['dillutions'], $invalid['reference']],
            [6931, 23, 422, '{"-1":"0.1"}', '{"ref_kve":"0"}'],
        ]];

        $this->withCsvExports($exports, function (string $directory) use ($attribute, $error): void {
            $this->artisan('legacy:import-csv', ['directory' => $directory])
                ->expectsOutputToContain("assayprofiles.csv: line 3: {$attribute} {$error}")
                ->expectsOutput('assayprofiles.csv: 2 imported, 1 failed.')
                ->expectsOutput('Imported 2 row(s) from 1 file(s).')
                ->expectsOutput('1 row(s) or file(s) failed to import.')
                ->assertExitCode(1);
        });

        $this->assertSame([119, 6931], AssayProfile::query()->orderBy('id')->pluck('id')->all());
        $this->assertSame(['ref_kve' => '0'], AssayProfile::query()->findOrFail(6931)->reference);
    }

    public static function invalidProfileJson(): array
    {
        return [
            'malformed dilutions' => ['dillutions', '{invalid', 'contains invalid JSON:'],
            'malformed references' => ['reference', '{invalid', 'contains invalid JSON:'],
            'numeric dilution scalar' => ['dillutions', '0', 'must contain a JSON object or array.'],
            'boolean reference scalar' => ['reference', 'false', 'must contain a JSON object or array.'],
            'text reference scalar' => ['reference', '"Afwezig"', 'contains invalid JSON:'],
            'excessive JSON encoding' => [
                'reference', json_encode(json_encode('{"ref_kve":"0"}', JSON_THROW_ON_ERROR), JSON_THROW_ON_ERROR),
                'must contain a JSON object or array.',
            ],
        ];
    }

    /**
     * @param  array<string, list<list<int|string>>>  $exports
     */
    private function withCsvExports(array $exports, Closure $callback): void
    {
        $directory = sys_get_temp_dir().DIRECTORY_SEPARATOR.uniqid('mesa-legacy-csv-');
        mkdir($directory);

        try {
            foreach ($exports as $filename => $rows) {
                $this->writeCsv($directory.DIRECTORY_SEPARATOR.$filename, $rows);
            }

            $callback($directory);
        } finally {
            foreach (array_keys($exports) as $filename) {
                $path = $directory.DIRECTORY_SEPARATOR.$filename;

                if (is_file($path)) {
                    unlink($path);
                }
            }

            rmdir($directory);
        }
    }

    /**
     * @param  list<list<int|string>>  $rows
     */
    private function writeCsv(string $path, array $rows): void
    {
        $handle = fopen($path, 'wb');
        $this->assertIsResource($handle);

        try {
            foreach ($rows as $row) {
                fputcsv($handle, $row, escape: '');
            }
        } finally {
            fclose($handle);
        }
    }
}
