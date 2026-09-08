<?php

namespace Tests\Feature;

use App\Actions\Assays\UpdateAssay;
use App\Models\Assay;
use App\Models\AssayField;
use App\Models\AssayType;
use App\Models\Matrix;
use App\Models\Media;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AssayAdministrationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');

        $this->migration()->up();
    }

    public function test_assay_configuration_and_revisions_preserve_legacy_storage(): void
    {
        $type = AssayType::create([
            'name' => 'Plate count', 'description' => '', 'added_by' => 42, 'added_date' => '1473249600',
        ]);
        $type->fields()->create([
            'name' => 'count', 'alias' => 'Count', 'type' => 'text', 'pos' => 2, 'filter' => 1,
        ]);
        $type->fields()->create([
            'name' => 'batch', 'alias' => 'Batch', 'type' => 'text', 'pos' => 1, 'filter' => null,
        ]);
        $field = AssayField::create(['name' => 'method', 'standard_value' => 'ISO', 'position' => 1]);
        $media = Media::create(['name' => 'Agar', 'supplements' => '{"0":"supplement"}']);
        $matrix = Matrix::create(['name' => 'Water']);

        $assay = Assay::create([
            'original_id' => 0,
            'name' => 'Total count',
            'type_base' => $type->id,
            'media_id' => json_encode([(string) $media->id]),
            'dillution' => 1,
            'replicates' => 1,
            'confirmation' => 0,
            'confirmation_script' => '{}',
            'type' => 1,
            'meta_assays' => '',
            'max_count' => 300,
            'min_count' => 10,
            'script' => '$result = $count;',
            'custom_fields' => '{"method":"ISO"}',
            'duration' => '48',
            'start_from' => 'p:incubation',
            'article_code' => '',
        ]);
        $assay->update(['original_id' => $assay->id]);
        $assay->matrices()->attach($matrix->id);

        $revision = $assay->replicate();
        $revision->save();
        $assay->update(['active' => 0]);
        $revision->refresh();

        $this->assertSame($type->id, $revision->assayType->id);
        $this->assertSame($assay->id, $revision->original->id);
        $this->assertSame([$assay->id, $revision->id], $revision->revisions->modelKeys());
        $this->assertSame([$assay->id, $revision->id], $type->assays()->orderBy('id')->pluck('id')->all());
        $this->assertSame(['batch', 'count'], $type->fields->pluck('name')->all());
        $this->assertSame($type->id, $type->fields->first()->assayType->id);
        $this->assertNull($type->fields->first()->filter);
        $this->assertSame([$matrix->id], $revision->matrices->modelKeys());
        $this->assertSame($assay->id, $revision->matrixContents->first()->originalAssay->id);
        $this->assertSame($matrix->id, $matrix->contents->first()->matrixDefinition->id);
        $this->assertSame('{"method":"ISO"}', $revision->custom_fields);
        $this->assertSame('{}', $revision->confirmation_script);
        $this->assertSame('', $revision->meta_assays);
        $this->assertSame('$result = $count;', $revision->script);
        $this->assertSame([(string) $media->id], json_decode($revision->media_id, true));
        $this->assertSame('{"0":"supplement"}', $media->fresh()->supplements);
        $this->assertSame('ISO', $field->fresh()->standard_value);
        $this->assertSame('1473249600', $type->fresh()->added_date);
        $this->assertEquals(1, $revision->confirmation_type);
        $this->assertEquals(5, $revision->confirmation_depth);
        $this->assertEquals(1, $revision->billable);
        $this->assertNull($revision->confirmation_support);
        $this->assertEquals(18, $media->fresh()->prediction_default_quant);
        $this->assertEquals(1, $media->fresh()->hasDate);

        $revision->update(['confirmation_depth' => null, 'billable' => null]);
        $this->assertNull($revision->fresh()->confirmation_depth);
        $this->assertNull($revision->fresh()->billable);
    }

    private function migration(): Migration
    {
        return require database_path('migrations/2026_09_07_130000_create_assay_administration_tables.php');
    }

    public function test_schema_matches_legacy_columns_and_can_be_rolled_back(): void
    {
        $sql = file_get_contents(base_path('legacy/app/sql_legacy.sql'));
        $tables = ['assaytypes', 'assaytypefields', 'assayfields', 'assays', 'media', 'matrix', 'matrixcontent'];

        foreach ($tables as $table) {
            preg_match('/CREATE TABLE `'.preg_quote($table, '/').'` \((.*?)\) ENGINE=/s', $sql, $definition);
            preg_match_all('/^  `([^`]+)`/m', $definition[1], $columns);

            $this->assertSame($columns[1], Schema::getColumnListing($table));
            $this->assertSame([], Schema::getForeignKeys($table));
        }

        $this->migration()->down();

        foreach ($tables as $table) {
            $this->assertFalse(Schema::hasTable($table));
        }

        $this->migration()->up();
        $this->assertTrue(Schema::hasTable('assays'));
    }

    public function test_updating_an_assay_creates_a_revision_and_preserves_shared_legacy_payloads(): void
    {
        $type = AssayType::create([
            'name' => 'Chemistry', 'description' => '', 'added_by' => 42, 'added_date' => '1473249600',
        ]);
        $field = AssayField::create(['name' => 'method', 'standard_value' => 'ISO', 'position' => 1]);
        $matrix = Matrix::create(['name' => 'Water']);
        $assay = Assay::create([
            'original_id' => 0,
            'name' => 'Initial assay',
            'type_base' => $type->id,
            'media_id' => '[]',
            'dillution' => 0,
            'replicates' => 0,
            'confirmation' => 0,
            'confirmation_script' => '{}',
            'type' => 1,
            'meta_assays' => '',
            'max_count' => 300,
            'min_count' => 0,
            'script' => '$result = $count;',
            'custom_fields' => '{"method":"ISO"}',
            'duration' => '2',
            'start_from' => 'r',
            'article_code' => '',
        ]);
        $assay->update(['original_id' => $assay->id]);
        $assay->matrices()->attach($matrix->id);

        $revision = app(UpdateAssay::class)->handle($assay->fresh(), [
            'name' => 'Revised assay',
            'type_base' => $type->id,
            'type' => 4,
            'media' => ['7', '8'],
            'meta_assays' => [$assay->id],
            'matrices' => [$matrix->id],
            'dillution' => 1,
            'replicates' => 1,
            'min_count' => 10,
            'max_count' => 500,
            'duration' => '3.5',
            'start_anchor' => 'p',
            'start_field_name' => 'received_at',
            'confirmation' => 1,
            'confirmation_type' => 1,
            'confirmation_init' => 2,
            'confirmation_depth' => 3,
            'confirmation_script' => '{"step":1}',
            'confirmation_support' => 'support',
            'show_conf_table' => 'table',
            'hide_report' => 1,
            'uses_indicator' => 0,
            'uses_trip_indicator' => 1,
            'billable' => 0,
            'article_code' => 'CHEM-1',
            'script' => '$result = $count + 1;',
            'custom_fields' => [$field->id => 'USP'],
        ]);

        $this->assertNotSame($assay->id, $revision->id);
        $this->assertSame($assay->id, $revision->original_id);
        $this->assertSame(0, $assay->fresh()->active);
        $this->assertSame('Revised assay', $revision->name);
        $this->assertSame('["7","8"]', $revision->media_id);
        $this->assertSame((string) $assay->id, $revision->meta_assays);
        $this->assertSame('p:received_at', $revision->start_from);
        $this->assertSame('{"method":"USP"}', $revision->custom_fields);
        $this->assertSame([$matrix->id], $revision->matrices->modelKeys());
        $this->assertSame([$assay->id, $revision->id], $revision->revisions->modelKeys());
    }
}
