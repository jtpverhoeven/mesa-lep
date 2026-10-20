<?php

namespace Tests\Feature;

use App\Jobs\CalculateAnalysisResultJob;
use App\Models\Assay;
use App\Models\Project;
use App\Models\ReferenceSource;
use App\Models\RoamingAnalysis;
use App\Models\Sample;
use App\Models\SampleAnalysis;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class SampleAnalysisReferencesTest extends TestCase
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

    public function test_editor_returns_current_values_and_all_workflow_outputs_with_only_available_sources(): void
    {
        $this->operator();
        config(['result-calculations.reference_outputs.multi' => ['ph' => 'pH', 'temperature' => 'Temperatuur']]);
        $analysis = $this->analysis('file:multi');
        $analysis->assayRecord->update(['active' => 0]);
        $analysis->roamingSettings->update(['reference' => ['ref_ph' => '7', 'ref_old' => '20']]);
        $global = $analysis->roamingSettings->reference_source;
        $client = ReferenceSource::create(['name' => ['en' => 'Client source'], 'client' => 1]);
        ReferenceSource::create(['name' => ['nl' => 'Other client'], 'client' => 2]);
        Gate::define('samples.assign-research', fn () => false);

        $this->getJson(route('samples.lookup.references', $this->parameters($analysis)))
            ->assertOk()
            ->assertJsonPath('data.analysis_id', $analysis->id)
            ->assertJsonPath('data.reference', ['ref_ph' => '7', 'ref_old' => '20'])
            ->assertJsonPath('data.reference_source', $global)
            ->assertJsonPath('data.configuration.reference_outputs', [
                ['key' => 'ref_ph', 'name' => 'ph', 'label' => 'pH'],
                ['key' => 'ref_temperature', 'name' => 'temperature', 'label' => 'Temperatuur'],
            ])
            ->assertJsonPath('data.reference_sources', [
                ['id' => $global, 'name' => 'Global source', 'client' => null],
                ['id' => $client->id, 'name' => 'Client source', 'client' => 1],
            ])
            ->assertJsonMissingPath('data.script');
    }

    #[DataProvider('referenceValues')]
    public function test_saving_reference_values_preserves_other_settings_and_invalidates_the_calculation(array $reference): void
    {
        $this->operator();
        $analysis = $this->analysis();
        $other = $this->analysis();
        $source = ReferenceSource::create(['name' => ['nl' => 'Client source'], 'client' => 1]);
        Queue::fake([CalculateAnalysisResultJob::class]);

        $this->patchJson(route('samples.lookup.references.update', $this->parameters($analysis)), [
            'reference' => $reference, 'reference_source' => $source->id,
            'replicates' => 99, 'dillutions' => [], 'profile' => 99,
        ])->assertOk()
            ->assertJsonPath('data.analyses.0.settings.reference', $reference)
            ->assertJsonPath('data.analyses.0.settings.reference_source', $source->id);

        $this->assertSame($reference, $analysis->roamingSettings->fresh()->reference);
        $this->assertDatabaseHas('roaminganalysis', [
            'said' => $analysis->id, 'reference_source' => $source->id,
            'dillutions' => '{"-1":"0.1"}', 'replicates' => 2, 'reference_scope' => 'test',
        ]);
        $this->assertDatabaseHas('sampleanalysis', ['id' => $analysis->id, 'profile' => 0, 'is_ready' => 0, 'storedResult' => null]);
        $this->assertDatabaseHas('sampleanalysis', ['id' => $other->id, 'is_ready' => 1]);
        $this->assertSame(['ref_result' => '10'], $other->roamingSettings->reference);
        Queue::assertPushed(CalculateAnalysisResultJob::class, fn ($job) => $job->analysisId === $analysis->id);
        Queue::assertPushed(CalculateAnalysisResultJob::class, 1);
    }

    public static function referenceValues(): array
    {
        return [
            'numeric zero' => [['ref_result' => 0]],
            'text zero' => [['ref_result' => '0']],
            'decimal' => [['ref_result' => '-2.5']],
            'qualitative legacy output' => [['ref_result' => 'Afwezig']],
        ];
    }

    public function test_multi_output_and_legacy_references_are_saved_without_a_32_character_limit(): void
    {
        $this->operator();
        config(['result-calculations.reference_outputs.multi' => ['ph' => 'pH', 'temperature' => 'Temperatuur']]);
        $analysis = $this->analysis('file:multi');
        $analysis->roamingSettings->update(['reference' => ['ref_old' => 'legacy']]);
        Queue::fake([CalculateAnalysisResultJob::class]);
        $reference = ['ref_ph' => '7.25', 'ref_temperature' => '22.5', 'ref_old' => 'legacy'];

        $this->patchJson(route('samples.lookup.references.update', $this->parameters($analysis)), [
            'reference' => $reference, 'reference_source' => $analysis->roamingSettings->reference_source,
        ])->assertOk()->assertJsonPath('data.analyses.0.settings.reference', $reference);

        $this->assertSame($reference, $analysis->roamingSettings->fresh()->reference);
    }

    public function test_editing_imported_maz7218_references_preserves_the_unprefixed_storage_key(): void
    {
        $this->operator();
        $analysis = $this->analysis('file:maz7218');
        $analysis->roamingSettings->update(['reference' => ['kve' => '100']]);
        $source = $analysis->roamingSettings->reference_source;
        Queue::fake([CalculateAnalysisResultJob::class]);

        $this->getJson(route('samples.lookup.references', $this->parameters($analysis)))
            ->assertOk()
            ->assertJsonPath('data.reference', ['kve' => '100'])
            ->assertJsonPath('data.configuration.reference_outputs', [
                ['key' => 'ref_kve', 'name' => 'kve', 'label' => 'kve'],
            ]);
        $this->patchJson(route('samples.lookup.references.update', $this->parameters($analysis)), [
            'reference' => ['kve' => '0'], 'reference_source' => $source,
        ])->assertOk()->assertJsonPath('data.analyses.0.settings.reference', ['kve' => '0']);

        $this->assertDatabaseHas('roaminganalysis', [
            'said' => $analysis->id, 'reference' => '{"kve":"0"}', 'reference_source' => $source,
        ]);
        Queue::assertPushed(CalculateAnalysisResultJob::class, fn ($job) => $job->analysisId === $analysis->id);
    }

    public function test_delete_clears_references_and_source_but_not_other_settings(): void
    {
        $this->operator();
        $analysis = $this->analysis();
        Queue::fake([CalculateAnalysisResultJob::class]);

        $this->patchJson(route('samples.lookup.references.update', $this->parameters($analysis)), [
            'reference' => (object) [], 'reference_source' => null,
        ])->assertOk()
            ->assertJsonPath('data.analyses.0.settings.reference', [])
            ->assertJsonPath('data.analyses.0.settings.reference_source', null);

        $this->assertDatabaseHas('roaminganalysis', [
            'said' => $analysis->id, 'reference' => '[]', 'reference_source' => null, 'replicates' => 2, 'reference_scope' => 'test',
        ]);
        Queue::assertPushed(CalculateAnalysisResultJob::class, fn ($job) => $job->analysisId === $analysis->id);
    }

    public function test_empty_fields_allow_no_source_and_are_not_saved_as_zero(): void
    {
        $this->operator();
        $analysis = $this->analysis();
        Queue::fake([CalculateAnalysisResultJob::class]);

        $this->patchJson(route('samples.lookup.references.update', $this->parameters($analysis)), [
            'reference' => ['ref_result' => '  '], 'reference_source' => null,
        ])->assertOk()->assertJsonPath('data.analyses.0.settings.reference', []);

        $this->assertSame([], $analysis->roamingSettings->fresh()->reference);
    }

    #[DataProvider('invalidReferences')]
    public function test_invalid_reference_payload_returns_422_without_changes_or_jobs(array $payload, string $field): void
    {
        $this->operator();
        $analysis = $this->analysis();
        $before = $analysis->roamingSettings->getAttributes();
        Queue::fake();

        $this->patchJson(route('samples.lookup.references.update', $this->parameters($analysis)), $payload)
            ->assertUnprocessable()->assertJsonValidationErrors($field);

        $this->assertSame($before, $analysis->roamingSettings->fresh()->getAttributes());
        $this->assertDatabaseHas('sampleanalysis', ['id' => $analysis->id, 'is_ready' => 1]);
        Queue::assertNothingPushed();
    }

    public static function invalidReferences(): array
    {
        return [
            'missing values' => [['reference_source' => null], 'reference'],
            'missing source' => [['reference' => []], 'reference_source'],
            'values must be a map' => [['reference' => 'bad', 'reference_source' => null], 'reference'],
            'source must be an ID' => [['reference' => [], 'reference_source' => 'bad'], 'reference_source'],
            'numeric zero requires source' => [['reference' => ['ref_result' => 0], 'reference_source' => null], 'reference_source'],
            'text zero requires source' => [['reference' => ['ref_result' => '0'], 'reference_source' => null], 'reference_source'],
            'nested values' => [['reference' => ['ref_result' => ['bad']], 'reference_source' => null], 'reference'],
            'unknown output' => [['reference' => ['ref_other' => '2'], 'reference_source' => null], 'reference'],
            'deleted source' => [['reference' => [], 'reference_source' => 9999], 'reference_source'],
        ];
    }

    public function test_another_clients_source_is_rejected_with_422(): void
    {
        $this->operator();
        $analysis = $this->analysis();
        $source = ReferenceSource::create(['client' => 2, 'name' => ['nl' => 'Other client']]);
        Queue::fake();

        $this->patchJson(route('samples.lookup.references.update', $this->parameters($analysis)), [
            'reference' => ['ref_result' => '3'], 'reference_source' => $source->id,
        ])->assertUnprocessable()->assertJsonPath('errors.reference_source.0', 'Deze referentiebron is niet beschikbaar voor de klant van dit monster.');

        $this->assertSame(['ref_result' => '10'], $analysis->roamingSettings->fresh()->reference);
        Queue::assertNothingPushed();
    }

    public function test_profile_analyses_cannot_be_read_or_changed_through_reference_endpoints(): void
    {
        $this->operator();
        $analysis = $this->analysis();
        $analysis->update(['profile' => 12]);
        Queue::fake();

        $this->getJson(route('samples.lookup.references', $this->parameters($analysis)))
            ->assertUnprocessable()->assertJsonValidationErrors('analysis');
        $this->patchJson(route('samples.lookup.references.update', $this->parameters($analysis)), [
            'reference' => [], 'reference_source' => null,
        ])->assertUnprocessable()->assertJsonValidationErrors('analysis');

        $this->assertSame(['ref_result' => '10'], $analysis->roamingSettings->fresh()->reference);
        Queue::assertNothingPushed();
    }

    #[DataProvider('frozenProjects')]
    public function test_frozen_projects_reject_reference_changes_with_422(string $field): void
    {
        $this->operator();
        $analysis = $this->analysis();
        $analysis->projectRecord->update([$field => 1]);
        Queue::fake();

        $this->patchJson(route('samples.lookup.references.update', $this->parameters($analysis)), [
            'reference' => [], 'reference_source' => null,
        ])->assertUnprocessable()->assertJsonPath('errors.sample.0', 'Dit project is vergrendeld of geautoriseerd.');

        $this->assertSame(['ref_result' => '10'], $analysis->roamingSettings->fresh()->reference);
        Queue::assertNothingPushed();
    }

    public static function frozenProjects(): array
    {
        return ['locked' => ['locked'], 'authorized' => ['auth_status']];
    }

    public function test_reference_endpoints_require_view_and_edit_permissions_and_authentication(): void
    {
        $this->operator();
        $analysis = $this->analysis();
        $parameters = $this->parameters($analysis);
        Gate::define('samples.update-research', fn () => false);
        $this->getJson(route('samples.lookup.references', $parameters))->assertForbidden();
        $this->patchJson(route('samples.lookup.references.update', $parameters), ['reference' => [], 'reference_source' => null])->assertForbidden();
        Gate::define('samples.update-research', fn () => true);
        Gate::define('samples.view', fn () => false);
        $this->getJson(route('samples.lookup.references', $parameters))->assertForbidden();
        $this->patchJson(route('samples.lookup.references.update', $parameters), ['reference' => [], 'reference_source' => null])->assertForbidden();
        auth()->logout();
        $this->getJson(route('samples.lookup.references', $parameters))->assertUnauthorized();
        $this->patchJson(route('samples.lookup.references.update', $parameters), ['reference' => [], 'reference_source' => null])->assertUnauthorized();
    }

    public function test_analysis_from_another_sample_returns_404_and_cannot_be_changed(): void
    {
        $this->operator();
        $analysis = $this->analysis();
        $other = $this->analysis();
        $parameters = ['sample' => $other->sample, 'analysis' => $analysis->id];
        Queue::fake();

        $this->getJson(route('samples.lookup.references', $parameters))->assertNotFound();
        $this->patchJson(route('samples.lookup.references.update', $parameters), ['reference' => [], 'reference_source' => null])->assertNotFound();

        $this->assertSame(['ref_result' => '10'], $analysis->roamingSettings->fresh()->reference);
        Queue::assertNothingPushed();
    }

    public function test_unknown_workflows_keep_existing_reference_keys_editable_without_inventing_outputs(): void
    {
        $this->operator();
        $analysis = $this->analysis('file:notported');
        Queue::fake([CalculateAnalysisResultJob::class]);

        $this->getJson(route('samples.lookup.references', $this->parameters($analysis)))
            ->assertOk()->assertJsonPath('data.configuration.reference_outputs', null);
        $this->patchJson(route('samples.lookup.references.update', $this->parameters($analysis)), [
            'reference' => ['ref_result' => '15'], 'reference_source' => $analysis->roamingSettings->reference_source,
        ])->assertOk();

        $this->assertSame(['ref_result' => '15'], $analysis->roamingSettings->fresh()->reference);
    }

    public function test_saving_unchanged_references_keeps_the_current_calculation(): void
    {
        $this->operator();
        $analysis = $this->analysis();
        Queue::fake();

        $this->patchJson(route('samples.lookup.references.update', $this->parameters($analysis)), [
            'reference' => ['ref_result' => '10'], 'reference_source' => $analysis->roamingSettings->reference_source,
        ])->assertOk();

        $this->assertSame(['output' => ['result' => 'old']], $analysis->fresh()->storedResult);
        $this->assertDatabaseHas('sampleanalysis', ['id' => $analysis->id, 'is_ready' => 1]);
        Queue::assertNothingPushed();
    }

    public function test_missing_roaming_settings_cannot_be_edited_or_silently_recreated(): void
    {
        $this->operator();
        $analysis = $this->analysis();
        $analysis->roamingSettings->delete();
        Queue::fake();

        $this->getJson(route('samples.lookup.references', $this->parameters($analysis)))->assertNotFound();
        $this->patchJson(route('samples.lookup.references.update', $this->parameters($analysis)), [
            'reference' => [], 'reference_source' => null,
        ])->assertUnprocessable()->assertJsonPath('errors.analysis.0', 'Deze analyse is geen losse analyse.');

        $this->assertDatabaseCount('roaminganalysis', 0);
        Queue::assertNothingPushed();
    }

    public function test_reference_changes_invalidate_and_queue_the_meta_parent_calculation(): void
    {
        $this->operator();
        $analysis = $this->analysis('file:legionellapassthrough');
        $parentAssay = $analysis->assayRecord->replicate();
        $parentAssay->fill(['name' => 'Meta analysis', 'type' => 4, 'script' => '']);
        $parentAssay->save();
        $parent = $analysis->sampleRecord->analyses()->create([
            'profile_group' => $analysis->profile_group, 'follow_number' => 2, 'profile' => 0,
            'assay' => $parentAssay->id, 'assay_base' => $parentAssay->id, 'predicted_end' => 1789171200,
            'project' => $analysis->project, 'is_ready' => true, 'storedResult' => ['output' => ['kve' => 'old']],
        ]);
        Queue::fake([CalculateAnalysisResultJob::class]);

        $this->patchJson(route('samples.lookup.references.update', $this->parameters($analysis)), [
            'reference' => ['ref_kve' => '5'], 'reference_source' => $analysis->roamingSettings->reference_source,
        ])->assertOk();

        $this->assertDatabaseHas('sampleanalysis', ['id' => $parent->id, 'is_ready' => 0, 'storedResult' => null]);
        Queue::assertPushed(CalculateAnalysisResultJob::class, fn ($job) => $job->analysisId === $parent->id);
        Queue::assertPushed(CalculateAnalysisResultJob::class, 2);
    }

    public function test_storage_migration_refuses_to_truncate_multi_output_references_on_rollback(): void
    {
        $analysis = $this->analysis();
        $reference = ['ref_ph' => '7.25', 'ref_temperature' => '22.5', 'ref_old' => 'legacy'];
        $analysis->roamingSettings->update(['reference' => $reference]);
        $migration = require database_path('migrations/2026_10_20_141757_expand_roaming_reference_values.php');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Cannot narrow roaming reference values without losing data.');
        $migration->down();
    }

    private function operator(): void
    {
        $this->actingAs(User::factory()->create(['enabled' => 1]));
        Gate::define('samples.view', fn () => true);
        Gate::define('samples.update-research', fn () => true);
    }

    private function parameters(SampleAnalysis $analysis): array
    {
        return ['sample' => $analysis->sample, 'analysis' => $analysis->id];
    }

    private function analysis(string $script = ''): SampleAnalysis
    {
        $project = Project::create(['client' => 1, 'subclient' => 0, 'project_name' => 'Reference project']);
        $sample = Sample::create([
            'barcode' => 'REF'.$project->id, 'follow_no' => 1, 'description' => 'Reference sample', 'sampling_method' => 1,
            'date_registered' => '1788998400', 'registered_by' => 1, 'client' => 1, 'subclient' => 0,
            'project' => $project->id, 'custom_fields' => '{}', 'predicted_end' => 1789171200,
            'sample_innoculated' => '', 'analyses_data' => '[]',
        ]);
        $assay = Assay::create([
            'original_id' => 1, 'name' => 'Reference assay', 'type_base' => 1, 'media_id' => '[]',
            'dillution' => 0, 'replicates' => 1, 'confirmation' => 0, 'confirmation_script' => '{}',
            'type' => 1, 'meta_assays' => '', 'max_count' => 300, 'min_count' => 10, 'script' => $script,
            'custom_fields' => '{}', 'duration' => '48', 'start_from' => '', 'article_code' => '',
        ]);
        $analysis = $sample->analyses()->create([
            'profile_group' => 1, 'follow_number' => 1, 'profile' => 0, 'assay' => $assay->id,
            'assay_base' => $assay->id, 'predicted_end' => 1789171200, 'project' => $project->id,
            'is_ready' => true, 'storedResult' => ['output' => ['result' => 'old']],
        ]);
        $source = ReferenceSource::create(['name' => ['nl' => 'Global source'], 'client' => null]);
        $settings = RoamingAnalysis::create([
            'said' => $analysis->id, 'assay' => $assay->id, 'dillutions' => ['-1' => '0.1'],
            'replicates' => 2, 'reference' => ['ref_result' => '10'], 'reference_scope' => 'test', 'reference_source' => $source->id,
        ]);
        $analysis->roamingAnalysis()->associate($settings);
        $analysis->save();

        return $analysis;
    }
}
