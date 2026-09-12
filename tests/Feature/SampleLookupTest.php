<?php

namespace Tests\Feature;

use App\Models\Assay;
use App\Models\Project;
use App\Models\Sample;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class SampleLookupTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');
        foreach (glob(database_path('migrations/*.php')) as $path) {
            (require $path)->up();
        }
        $this->actingAs(User::factory()->create(['enabled' => 1]));
        foreach (['samples.view', 'samples.assign-research', 'samples.update-research'] as $permission) {
            Gate::define($permission, fn () => true);
        }
    }

    public function test_lookup_returns_legacy_data_and_project_navigation_without_sensitive_fields(): void
    {
        $sample = $this->sample('26091000');
        $next = $this->sample('26091001', $sample->project);
        $this->get(route('samples.lookup'))->assertOk()->assertSee('sample-lookup');
        $this->getJson(route('samples.lookup.data', ['barcode' => ' 26091000 ']))
            ->assertOk()
            ->assertJsonPath('data.sample.id', $sample->id)
            ->assertJsonPath('data.project_follow_number', 1)
            ->assertJsonPath('data.sample_fields.0.value', '4 C')
            ->assertJsonPath('data.project.project_name', 'Project water')
            ->assertJsonPath('data.project_samples.1.barcode', $next->barcode)
            ->assertJsonPath('data.previous', null)
            ->assertJsonPath('data.next', $next->barcode)
            ->assertJsonMissingPath('data.project.lock_pass');
        $this->getJson(route('samples.lookup.data', ['barcode' => $next->barcode]))
            ->assertOk()->assertJsonPath('data.project_follow_number', 2);
        $this->getJson(route('samples.lookup.data', ['barcode' => 'unknown']))->assertNotFound();
        $this->getJson(route('samples.lookup.data'))->assertUnprocessable();
    }

    public function test_lookup_and_mutations_require_their_own_permissions(): void
    {
        $sample = $this->sample('26091000');
        Gate::define('samples.view', fn () => false);
        $this->getJson(route('samples.lookup.data', ['barcode' => $sample->barcode]))->assertForbidden();
        Gate::define('samples.view', fn () => true);
        Gate::define('samples.assign-research', fn () => false);
        $this->postJson(route('samples.lookup.research', $sample), ['operation' => 'add'])->assertForbidden();
        Gate::define('samples.update-research', fn () => false);
        $this->postJson(route('samples.lookup.research', $sample), ['operation' => 'remove', 'analysis_id' => 1])->assertForbidden();
    }

    public function test_research_can_be_added_reordered_and_removed_only_from_its_sample(): void
    {
        $sample = $this->sample('26091000');
        $assay = $this->assay();
        $url = route('samples.lookup.research', $sample);
        $payload = ['operation' => 'add', 'analyses' => [['type' => 'assay', 'assay_id' => $assay->id, 'settings' => ['replicates' => 2]]]];
        $this->postJson($url, $payload)->assertOk()->assertJsonPath('data.analyses.0.name', 'Total count');
        $this->postJson($url, $payload)->assertOk();
        $ids = $sample->analyses()->pluck('id')->all();
        $this->postJson($url, ['operation' => 'reorder', 'analysis_ids' => [$ids[0]]])->assertUnprocessable();
        $this->postJson($url, ['operation' => 'reorder', 'analysis_ids' => array_reverse($ids)])
            ->assertOk()->assertJsonPath('data.analyses.0.id', $ids[1]);
        $other = $this->sample('26091001');
        $this->postJson(route('samples.lookup.research', $other), ['operation' => 'remove', 'analysis_id' => $ids[0]])->assertNotFound();
        $this->postJson($url, ['operation' => 'remove', 'analysis_id' => $ids[0]])->assertOk()->assertJsonCount(1, 'data.analyses');
        $this->assertDatabaseMissing('roaminganalysis', ['said' => $ids[0]]);
        $sample->analyses()->first()->update(['is_ready' => true]);
        $this->postJson($url, ['operation' => 'remove', 'analysis_id' => $ids[1]])->assertUnprocessable();
        Project::find($sample->project)->update(['locked' => 1]);
        $this->postJson($url, $payload)->assertUnprocessable();
    }

    public function test_addition_rolls_back_when_a_later_entry_is_unavailable(): void
    {
        $sample = $this->sample('26091000');
        $assay = $this->assay();
        $inactive = $this->assay();
        $inactive->update(['active' => 0]);
        $this->postJson(route('samples.lookup.research', $sample), ['operation' => 'add', 'analyses' => [
            ['type' => 'assay', 'assay_id' => $assay->id, 'settings' => ['replicates' => 1]],
            ['type' => 'assay', 'assay_id' => $inactive->id, 'settings' => ['replicates' => 1]],
        ]])->assertUnprocessable();
        $this->assertDatabaseCount('sampleanalysis', 0);
        $this->assertDatabaseCount('roaminganalysis', 0);
        $this->assertSame(1, $sample->fresh()->isEmpty);
    }

    public function test_database_rejects_duplicate_barcodes(): void
    {
        $this->sample('26091000');
        $this->expectException(UniqueConstraintViolationException::class);
        $this->sample('26091000');
    }

    private function sample(string $barcode, ?int $projectId = null): Sample
    {
        $projectId ??= Project::create(['client' => 1, 'subclient' => 0, 'project_name' => 'Project water', 'lock_pass' => 'private'])->id;

        return Sample::create([
            'barcode' => $barcode, 'follow_no' => 1000, 'description' => 'Water sample',
            'sampling_method' => 1, 'date_registered' => '1788998400', 'registered_by' => 1,
            'client' => 1, 'subclient' => 0, 'project' => $projectId,
            'custom_fields' => '{"temperature":"4 C"}', 'predicted_end' => 1789171200,
            'sample_innoculated' => '', 'analyses_data' => '[]',
        ]);
    }

    private function assay(): Assay
    {
        return Assay::create([
            'original_id' => 1, 'name' => 'Total count', 'type_base' => 1, 'media_id' => '[]',
            'dillution' => 0, 'replicates' => 1, 'confirmation' => 0, 'confirmation_script' => '{}',
            'type' => 1, 'meta_assays' => '', 'max_count' => 300, 'min_count' => 10,
            'script' => '', 'custom_fields' => '{}', 'duration' => '48', 'start_from' => '', 'article_code' => '',
        ]);
    }
}
