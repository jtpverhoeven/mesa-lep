<?php

namespace Tests\Feature;

use App\Models\Media;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ImportCsvCommandTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');

        $this->migration()->up();
    }

    public function test_csv_rows_are_imported_as_new_models(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'mesa-csv-');
        file_put_contents($path, "name,short_name\n\"Agar, enriched\",AE\n");

        try {
            $this->artisan('db:import-csv', [
                'filename' => $path,
                'model' => 'Media',
            ])->assertExitCode(0);
        } finally {
            unlink($path);
        }

        $this->assertDatabaseHas('media', [
            'name' => 'Agar, enriched',
            'short_name' => 'AE',
        ]);
        $this->assertSame(1, Media::count());
    }

    private function migration(): Migration
    {
        return require database_path('migrations/2026_09_07_130000_create_assay_administration_tables.php');
    }
}