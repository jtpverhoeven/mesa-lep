<?php

namespace Tests\Feature;

use App\Actions\Assays\CreateAssay;
use App\Actions\Assays\UpdateAssay;
use App\Http\Controllers\AssayController;
use App\Http\Requests\StoreAssayRequest;
use App\Models\AssayType as AssayTypeModel;
use App\Models\Matrix;
use App\Models\Media;
use Illuminate\Contracts\Validation\Validator as ValidatorContract;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class AssayConfirmationAdministrationTest extends TestCase
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

        $this->migration()->up();
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

    public function test_create_assay_normalizes_confirmation_chain_and_support_ids(): void
    {
        [$type, $matrix, $chainMedia, $supportMedia] = $this->fixtures();

        $assay = app(CreateAssay::class)->handle($this->payload($type, $matrix, $chainMedia, $supportMedia));

        $this->assertSame(
            json_encode([
                ['mediaId' => $chainMedia->id, 'chainId' => 1, 'disposition' => '+'],
                ['mediaId' => $chainMedia->id, 'chainId' => 2, 'disposition' => '?'],
            ], JSON_THROW_ON_ERROR),
            $assay->confirmation_script,
        );
        $this->assertSame(
            json_encode([['mediaId' => $supportMedia->id, 'chainId' => 1]], JSON_THROW_ON_ERROR),
            $assay->confirmation_support,
        );
        $this->assertSame(1, $assay->confirmation);
        $this->assertSame(1, $assay->confirmation_type);
        $this->assertSame(0, $assay->confirmation_init);
        $this->assertSame(4, $assay->confirmation_depth);
        $this->assertSame('2', $assay->show_conf_table);
    }

    public function test_update_preserves_confirmation_payload_when_it_is_not_submitted(): void
    {
        [$type, $matrix, $chainMedia, $supportMedia] = $this->fixtures();
        $createAssay = app(CreateAssay::class);
        $assay = $createAssay->handle($this->payload($type, $matrix, $chainMedia, $supportMedia));

        $revised = app(UpdateAssay::class)->handle($assay->fresh(), [
            'name' => 'Revised assay',
            'type_base' => $type->id,
            'type' => 1,
            'media' => [$chainMedia->id],
            'matrices' => [$matrix->id],
            'dillution' => 1,
            'replicates' => 1,
            'min_count' => 1,
            'max_count' => 100,
            'duration' => '2',
            'start_anchor' => 'r',
            'hide_report' => 0,
            'uses_indicator' => 1,
            'uses_trip_indicator' => 1,
            'billable' => 1,
            'article_code' => '',
            'script' => '',
        ]);

        $this->assertSame($assay->confirmation_script, $revised->confirmation_script);
        $this->assertSame($assay->confirmation_support, $revised->confirmation_support);
        $this->assertSame($assay->show_conf_table, $revised->show_conf_table);
    }

    public function test_edit_payload_keeps_referenced_inactive_media_visible(): void
    {
        [$type, $matrix, $chainMedia, $supportMedia] = $this->fixtures();
        $inactiveMedia = Media::create([
            'name' => 'Historical confirmation medium',
            'active' => 0,
        ]);
        $assay = app(CreateAssay::class)->handle($this->payload($type, $matrix, $chainMedia, $supportMedia));
        $assay->update([
            'confirmation_script' => json_encode([
                ['mediaId' => $inactiveMedia->id, 'chainId' => 1, 'disposition' => '+'],
            ], JSON_THROW_ON_ERROR),
        ]);

        $view = app(AssayController::class)->edit($assay->fresh());
        $historicalOption = collect($view->getData()['confirmationMedia'])
            ->firstWhere('id', (string) $inactiveMedia->id);

        $this->assertSame([
            'id' => (string) $inactiveMedia->id,
            'name' => 'Historical confirmation medium',
            'short_name' => null,
            'active' => false,
        ], $historicalOption);
    }

    public function test_edit_payload_keeps_referenced_inactive_meta_assays_visible(): void
    {
        [$type, $matrix, $chainMedia, $supportMedia] = $this->fixtures();
        $inactiveMetaAssay = app(CreateAssay::class)->handle($this->payload($type, $matrix, $chainMedia, $supportMedia));
        $inactiveMetaAssay->update(['active' => 0]);

        $metaAssayPayload = $this->payload($type, $matrix, $chainMedia, $supportMedia);
        $metaAssayPayload['name'] = 'Meta assay with historical selection';
        $metaAssayPayload['type'] = 4;
        $metaAssayPayload['meta_assays'] = [$inactiveMetaAssay->id];
        $assay = app(CreateAssay::class)->handle($metaAssayPayload);

        $view = app(AssayController::class)->edit($assay->fresh());
        $historicalOption = collect($view->getData()['assays'])
            ->firstWhere('id', $inactiveMetaAssay->id);

        $this->assertSame($inactiveMetaAssay->id, $historicalOption?->id);
        $this->assertSame($inactiveMetaAssay->name, $historicalOption?->name);
    }

    public function test_edit_payload_resolves_historical_meta_assay_to_active_revision(): void
    {
        [$type, $matrix, $chainMedia, $supportMedia] = $this->fixtures();
        $historicalMetaAssay = app(CreateAssay::class)->handle($this->payload($type, $matrix, $chainMedia, $supportMedia));
        $historicalMetaAssay->update(['active' => 0]);
        $activeMetaAssay = $historicalMetaAssay->replicate();
        $activeMetaAssay->active = 1;
        $activeMetaAssay->save();

        $metaAssayPayload = $this->payload($type, $matrix, $chainMedia, $supportMedia);
        $metaAssayPayload['name'] = 'Meta assay with a revised selection';
        $metaAssayPayload['type'] = 4;
        $metaAssayPayload['meta_assays'] = [$historicalMetaAssay->id];
        $assay = app(CreateAssay::class)->handle($metaAssayPayload);

        $view = app(AssayController::class)->edit($assay->fresh());
        $options = collect($view->getData()['assays']);

        $this->assertSame([(string) $activeMetaAssay->id], $view->getData()['selectedMetaAssays']);
        $this->assertSame($activeMetaAssay->id, $options->firstWhere('id', $activeMetaAssay->id)?->id);
        $this->assertNull($options->firstWhere('id', $historicalMetaAssay->id));
    }

    public function test_store_request_rejects_malformed_confirmation_json(): void
    {
        $request = TestableStoreAssayRequest::create('/admin/assays', 'POST', [
            'confirmation_script' => '{malformed',
            'confirmation_support' => '[]',
        ]);
        $request->normalizePayloadForTest();
        $validator = Validator::make($request->all(), $request->rules());
        $request->attachValidatorForTest($validator);

        $this->assertFalse($validator->passes());
        $this->assertTrue($validator->errors()->has('confirmation_script'));
    }

    private function fixtures(): array
    {
        $type = AssayTypeModel::create([
            'name' => 'Plate count',
            'description' => '',
            'added_by' => 1,
            'added_date' => '12-09-2026',
        ]);
        $matrix = Matrix::create(['name' => 'Water']);
        $chainMedia = Media::create([
            'name' => 'Confirmation medium',
            'confirmation_media' => 1,
            'active' => 1,
        ]);
        $supportMedia = Media::create([
            'name' => 'Support medium',
            'type' => 3,
            'active' => 1,
        ]);

        return [$type, $matrix, $chainMedia, $supportMedia];
    }

    private function payload(AssayTypeModel $type, Matrix $matrix, Media $chainMedia, Media $supportMedia): array
    {
        return [
            'name' => 'Confirmation assay',
            'type_base' => $type->id,
            'type' => 1,
            'media' => [$chainMedia->id],
            'matrices' => [$matrix->id],
            'meta_assays' => [],
            'dillution' => 1,
            'replicates' => 1,
            'min_count' => 1,
            'max_count' => 100,
            'duration' => '2',
            'start_from' => 'r',
            'confirmation' => 1,
            'confirmation_type' => 1,
            'confirmation_init' => 0,
            'confirmation_depth' => 4,
            'confirmation_script' => [
                ['mediaId' => $chainMedia->id, 'chainId' => 99, 'disposition' => '+'],
                ['mediaId' => $chainMedia->id, 'chainId' => 3, 'disposition' => '?'],
            ],
            'confirmation_support' => [
                ['mediaId' => $supportMedia->id, 'chainId' => 22],
            ],
            'show_conf_table' => '2',
            'hide_report' => 0,
            'uses_indicator' => 1,
            'uses_trip_indicator' => 1,
            'billable' => 1,
            'article_code' => '',
            'script' => '',
            'custom_fields' => [],
        ];
    }

    private function migration(): Migration
    {
        return require database_path('migrations/2026_09_07_130000_create_assay_administration_tables.php');
    }

    private function confirmationMigration(): Migration
    {
        return require database_path('migrations/2026_09_12_000000_create_confirmation_tables.php');
    }
}

class TestableStoreAssayRequest extends StoreAssayRequest
{
    public function normalizePayloadForTest(): void
    {
        $this->prepareForValidation();
    }

    public function attachValidatorForTest(ValidatorContract $validator): void
    {
        $this->withValidator($validator);
    }
}
