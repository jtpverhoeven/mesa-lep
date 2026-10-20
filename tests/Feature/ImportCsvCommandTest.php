<?php

namespace Tests\Feature;

use App\Models\AssayProfile;
use App\Models\Media;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
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

    public function test_profile_json_settings_are_decoded_before_creating_models(): void
    {
        $this->researchProfileMigration()->up();
        $path = tempnam(sys_get_temp_dir(), 'mesa-csv-');
        $handle = fopen($path, 'wb');

        try {
            fputcsv($handle, ['research_profile', 'assay', 'dillutions', 'reference'], escape: '');
            fputcsv($handle, [23, 422, '{"-2":"0.01"}', '{"ref_note":"Below \\"limit\\"","ref_kve":"0"}'], escape: '');
        } finally {
            fclose($handle);
        }

        try {
            $this->artisan('db:import-csv', ['filename' => $path, 'model' => 'AssayProfile'])
                ->assertSuccessful();
        } finally {
            unlink($path);
        }

        $profile = AssayProfile::query()->sole();
        $this->assertSame(23, $profile->research_profile);
        $this->assertSame(422, $profile->assay);
        $this->assertSame(['-2' => '0.01'], $profile->dillutions);
        $this->assertSame(['ref_note' => 'Below "limit"', 'ref_kve' => '0'], $profile->reference);
        $this->assertSame('{"-2":"0.01"}', $profile->getRawOriginal('dillutions'));
    }

    public function test_invalid_profile_json_fails_instead_of_importing_empty_settings(): void
    {
        $this->researchProfileMigration()->up();
        $path = tempnam(sys_get_temp_dir(), 'mesa-csv-');
        file_put_contents($path, "research_profile,assay,dillutions,reference\n23,422,{invalid,{}\n");

        try {
            $this->artisan('db:import-csv', ['filename' => $path, 'model' => 'AssayProfile'])
                ->expectsOutput('CSV import failed.')
                ->expectsOutputToContain('dillutions contains invalid JSON:')
                ->assertExitCode(1);
        } finally {
            unlink($path);
        }

        $this->assertDatabaseCount('assayprofiles', 0);
    }

    public function test_generic_import_still_dispatches_model_creation_events_for_each_row(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'mesa-csv-');
        file_put_contents($path, "name,short_name\nFirst agar,A\nSecond agar,B\nThird agar,C\n");
        Event::fake(['eloquent.created: '.Media::class]);

        try {
            $this->artisan('db:import-csv', ['filename' => $path, 'model' => 'Media'])
                ->assertSuccessful();
        } finally {
            unlink($path);
        }

        $this->assertDatabaseCount('media', 3);
        Event::assertDispatchedTimes('eloquent.created: '.Media::class, 3);
    }

    private function researchProfileMigration(): Migration
    {
        return require database_path('migrations/2026_09_10_000000_create_research_profile_administration_tables.php');
    }

    private function migration(): Migration
    {
        return require database_path('migrations/2026_09_07_130000_create_assay_administration_tables.php');
    }
}
