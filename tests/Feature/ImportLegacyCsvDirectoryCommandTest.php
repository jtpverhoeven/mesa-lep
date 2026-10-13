<?php

namespace Tests\Feature;

use App\Models\LabelDesign;
use App\Models\LabelEvent;
use App\Models\Printer;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
