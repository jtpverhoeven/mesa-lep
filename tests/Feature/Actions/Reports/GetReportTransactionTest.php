<?php

namespace Tests\Feature\Actions\Reports;

use App\Actions\Reports\GetReportTransaction;
use App\Models\Export;
use App\Models\Project;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class GetReportTransactionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');

        foreach (glob(database_path('migrations/*.php')) as $path) {
            (require $path)->up();
        }
    }

    public function test_transaction_exports_are_ordered_and_relations_are_eager_loaded(): void
    {
        Project::query()->create(['client' => 1, 'subclient' => 0]);
        Export::factory()->create(['id' => 22, 'transaction' => 8]);
        Export::factory()->create(['id' => 11, 'transaction' => 8]);
        Export::factory()->create(['transaction' => 9]);

        $exports = app(GetReportTransaction::class)->handle(8);

        $this->assertSame([11, 22], $exports->modelKeys());
        $this->assertTrue($exports[0]->relationLoaded('projectRecord'));
        $this->assertTrue($exports[0]->relationLoaded('clientRecord'));
        $this->assertTrue($exports[0]->projectRecord->relationLoaded('client'));
    }

    public function test_missing_transactions_raise_a_typed_model_not_found_exception(): void
    {
        $this->expectException(ModelNotFoundException::class);

        app(GetReportTransaction::class)->handle(404);
    }

    public function test_pdf_existence_uses_the_report_storage_path_on_the_local_disk(): void
    {
        Storage::fake('local');
        $export = Export::factory()->withPdf()->create();

        $this->assertTrue($export->pdfExists());

        Storage::disk('local')->delete($export->storagePath());
        $this->assertFalse($export->pdfExists());
    }
}
