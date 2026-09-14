<?php

namespace Tests\Feature;

use App\Models\Confirmation;
use App\Models\ConfKeyStore;
use App\Models\SampleAnalysis;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ConfirmationPersistenceTest extends TestCase
{
    private mixed $originalDefaultConnection;

    private mixed $originalSqliteDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->originalDefaultConnection = config('database.default');
        $this->originalSqliteDatabase = config('database.connections.sqlite.database');

        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');

        $this->sampleAnalysisMigration()->up();
        $this->confirmationMigration()->up();
    }

    protected function tearDown(): void
    {
        DB::purge('sqlite');
        config([
            'database.default' => $this->originalDefaultConnection,
            'database.connections.sqlite.database' => $this->originalSqliteDatabase,
        ]);

        parent::tearDown();
    }

    #[DataProvider('legacyPayloads')]
    public function test_legacy_payloads_round_trip_with_object_shaped_paths(
        string $fixture,
        string $scope,
        string $expectedAnswer,
    ): void {
        $payload = json_decode(
            file_get_contents(base_path('tests/Fixtures/Confirmations/'.$fixture)),
            true,
            512,
            JSON_THROW_ON_ERROR,
        );

        $confirmation = Confirmation::create([
            'said' => 42,
            'racetrack' => $payload['racetrack'],
            'metadata' => $payload['metadata'],
            'in_use' => $payload['in_use'],
            'data' => $payload['data'],
        ])->fresh();

        $this->assertSame($payload['racetrack'], $confirmation->racetrack);
        $this->assertSame($payload['metadata'], $confirmation->metadata);
        $this->assertSame($payload['in_use'], $confirmation->in_use);
        $this->assertFalse($confirmation->isReady);

        $rawRaceTrack = json_decode(
            $confirmation->getRawOriginal('racetrack'),
            false,
            512,
            JSON_THROW_ON_ERROR,
        );

        $this->assertIsObject($rawRaceTrack);
        $this->assertIsObject($rawRaceTrack->{$scope});
        $this->assertSame(
            json_encode($payload['racetrack'], JSON_FORCE_OBJECT | JSON_THROW_ON_ERROR),
            $confirmation->getRawOriginal('racetrack'),
        );

        $answer = $scope === 'global'
            ? $confirmation->racetrack['global'][0][0][0]
            : $confirmation->racetrack['0.1'][0][0][0];

        $this->assertSame($expectedAnswer, $answer);
    }

    public function test_imported_array_payloads_are_read_and_rewritten_as_objects(): void
    {
        DB::table('confirmations')->insert([
            'said' => 43,
            'note' => null,
            'in_use' => '[]',
            'racetrack' => '[]',
            'metadata' => '{}',
            'isReady' => 0,
            'data' => '[]',
        ]);

        $confirmation = Confirmation::query()->where('said', 43)->firstOrFail();

        $this->assertSame([], $confirmation->racetrack);
        $this->assertSame([], $confirmation->in_use);
        $this->assertSame([], $confirmation->data);

        $confirmation->racetrack = $confirmation->racetrack;
        $confirmation->save();

        $this->assertSame('{}', $confirmation->getRawOriginal('racetrack'));
    }

    public function test_schema_preserves_legacy_columns_and_indexes(): void
    {
        $legacySql = file_get_contents(base_path('legacy/app/sql_legacy.sql'));

        foreach (['confirmations', 'confirmationtables', 'confkeystore'] as $table) {
            preg_match(
                '/CREATE TABLE `'.preg_quote($table, '/').'` \((.*?)\) ENGINE=/s',
                $legacySql,
                $definition,
            );
            preg_match_all('/^  `([^`]+)`/m', $definition[1], $columns);

            $this->assertSame($columns[1], Schema::getColumnListing($table));
        }

        $this->assertTrue(Schema::hasIndex('confirmations', 'idx_confirmations_said'));
        $this->assertTrue(Schema::hasIndex('confirmations', 'idx_confirmations_id_said'));
        $this->assertTrue(Schema::hasIndex('confirmations', 'id'));
        $this->assertTrue(Schema::hasIndex('confkeystore', 'confkeystore_id'));
    }

    public function test_confirmation_relationships_use_the_legacy_said_key(): void
    {
        $analysis = SampleAnalysis::create([
            'profile_group' => 1,
            'sample' => 1,
            'follow_number' => 1,
            'profile' => 0,
            'assay' => 0,
            'assay_base' => 1,
            'roaming_id' => null,
            'predicted_end' => 0,
            'original_assay_base' => null,
            'conf_requested' => 0,
            'is_ready' => 0,
            'project' => null,
            'project_order' => 1,
            'storedResult' => null,
        ]);
        $confirmation = Confirmation::create(['said' => $analysis->id]);

        $this->assertSame($confirmation->id, $analysis->fresh()->confirmationRecord->id);
        $this->assertSame($analysis->id, $confirmation->fresh()->sampleAnalysis->id);
    }

    public function test_conf_key_store_persists_legacy_lookup_fields(): void
    {
        $key = ConfKeyStore::create([
            'innocdate' => '12-09-2026',
            'media' => 5,
            'param' => 'poscontrol',
            'value' => '42',
        ]);

        $this->assertSame(1, $key->id);
        $this->assertSame([
            'innocdate' => '12-09-2026',
            'media' => 5,
            'param' => 'poscontrol',
            'value' => '42',
        ], $key->fresh()->only(['innocdate', 'media', 'param', 'value']));
    }

    public static function legacyPayloads(): array
    {
        return [
            'global scope' => ['global.json', 'global', '+'],
            'per plate scope' => ['per_plate.json', '0.1', '+'],
        ];
    }

    private function sampleAnalysisMigration(): Migration
    {
        return require database_path('migrations/2026_09_10_020000_create_sample_analysis_tables.php');
    }

    private function confirmationMigration(): Migration
    {
        return require database_path('migrations/2026_09_12_000000_create_confirmation_tables.php');
    }
}
