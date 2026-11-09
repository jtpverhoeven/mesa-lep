<?php

namespace Tests\Feature\Actions\Reports;

use App\Actions\Reports\ResolveExportSampleScope;
use App\Models\Export;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;
use UnexpectedValueException;

class ResolveExportSampleScopeTest extends TestCase
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

    public function test_json_scope_is_returned_and_empty_scope_does_not_fall_back_to_sample_id(): void
    {
        $export = Export::factory()->withSampleScope([42, 84])->create(['sample_id' => 99]);
        $emptyScope = Export::factory()->withSampleScope([])->create(['sample_id' => 99]);

        $this->assertSame([42, 84], app(ResolveExportSampleScope::class)->handle($export));
        $this->assertSame([], app(ResolveExportSampleScope::class)->handle($emptyScope));
    }

    public function test_legacy_sample_id_is_used_when_no_scope_is_stored(): void
    {
        $export = Export::factory()->create(['sample_id' => 19, 'sample_id_scope' => null]);
        $exportWithoutSample = Export::factory()->create(['sample_id' => null, 'sample_id_scope' => null]);
        $resolver = app(ResolveExportSampleScope::class);

        $this->assertSame([19], $resolver->handle($export));
        $this->assertSame([], $resolver->handle($exportWithoutSample));
    }

    public function test_malformed_scope_is_reported_instead_of_silently_becoming_empty(): void
    {
        $export = Export::factory()->create();
        DB::table('exports')->where('id', $export->id)->update(['sample_id_scope' => '{invalid']);
        $export->refresh();

        $this->expectException(UnexpectedValueException::class);

        app(ResolveExportSampleScope::class)->handle($export);
    }
}
