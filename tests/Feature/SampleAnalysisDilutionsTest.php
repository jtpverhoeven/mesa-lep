<?php

namespace Tests\Feature;

use App\Jobs\CalculateAnalysisResultJob;
use App\Models\Assay;
use App\Models\Project;
use App\Models\Result;
use App\Models\Sample;
use App\Models\SampleAnalysis;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class SampleAnalysisDilutionsTest extends TestCase
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

    public function test_editor_returns_the_dilutions_held_by_the_result_rows(): void
    {
        $this->operator();
        $analysis = $this->analysis([['1', 0], ['1', 1], ['0.1', 0], ['0.1', 1], ['0.1', 2]]);

        $this->getJson(route('samples.lookup.dilutions', $this->parameters($analysis)))
            ->assertOk()
            ->assertJsonPath('data.analysis_id', $analysis->id)
            ->assertJsonPath('data.uses_dilution', true)
            ->assertJsonPath('data.uses_replicates', true)
            ->assertJsonPath('data.dilutions', [
                ['value' => '1', 'replicates' => 1],
                ['value' => '0.1', 'replicates' => 2],
            ]);
    }

    public function test_the_permission_is_required_to_read_and_change_dilutions(): void
    {
        $this->operator(permitted: false);
        $analysis = $this->analysis([['1', 0]]);
        Queue::fake();

        $this->getJson(route('samples.lookup.dilutions', $this->parameters($analysis)))->assertForbidden();
        $this->patchJson(route('samples.lookup.dilutions.update', $this->parameters($analysis)), [
            'dilutions' => [['value' => '1', 'replicates' => 0], ['value' => '0.1', 'replicates' => 0]],
        ])->assertForbidden();

        $this->assertSame(1, Result::query()->where('sa_id', $analysis->id)->count());
        Queue::assertNothingPushed();
    }

    public function test_saving_keeps_entered_results_adds_missing_rows_and_removes_the_rest(): void
    {
        $this->operator();
        $analysis = $this->analysis([['0.1', 0], ['0.1', 1], ['0.01', 0], ['0.001', 0]]);
        Result::query()->where('sa_id', $analysis->id)->where('df', '0.1')->where('rep', 0)->update(['data' => json_encode(['kve' => '12'])]);
        Queue::fake([CalculateAnalysisResultJob::class]);

        $this->patchJson(route('samples.lookup.dilutions.update', $this->parameters($analysis)), [
            'dilutions' => [
                ['value' => '0.01', 'replicates' => 0],
                ['value' => '0.1', 'replicates' => 0],
                ['value' => '1', 'replicates' => 1],
            ],
        ])->assertOk()->assertJsonPath('data.sample.id', $analysis->sample);

        $rows = Result::query()->where('sa_id', $analysis->id)->orderBy('follow_no')->get();
        $this->assertSame(['0.1:0', '0.01:0', '1:0', '1:1'], $rows->map(fn ($row) => "{$row->df}:{$row->rep}")->all());
        $this->assertSame(['12'], [$rows[0]->data['kve']]);
        $this->assertSame([1, 3, 4, 5], $rows->pluck('follow_no')->all());
        $this->assertSame(['sample' => $analysis->sample, 'roaming_id' => 0], $rows->last()->only(['sample', 'roaming_id']));
        $this->assertDatabaseHas('sampleanalysis', ['id' => $analysis->id, 'is_ready' => 0, 'storedResult' => null]);
        Queue::assertPushed(CalculateAnalysisResultJob::class, fn ($job) => $job->analysisId === $analysis->id);
        $this->assertDatabaseHas('changetracker', [
            'type' => '10', 'sample' => $analysis->sample, 'said' => $analysis->id,
            'to' => "0=1\n0=1\n-1=0.1\n-2=0.01",
        ]);
    }

    public function test_only_the_result_rows_change_not_the_shared_settings(): void
    {
        $this->operator();
        $analysis = $this->analysis([['0.1', 0]]);
        Queue::fake([CalculateAnalysisResultJob::class]);

        $this->patchJson(route('samples.lookup.dilutions.update', $this->parameters($analysis)), [
            'dilutions' => [['value' => '0.1', 'replicates' => 0], ['value' => '0.01', 'replicates' => 0]],
        ])->assertOk();

        $this->assertSame(2, Result::query()->where('sa_id', $analysis->id)->count());
        $this->assertSame(0, DB::table('roaminganalysis')->count());
    }

    #[DataProvider('invalidCurves')]
    public function test_invalid_curves_are_rejected_without_changes(array $dilutions, string $message): void
    {
        $this->operator();
        $analysis = $this->analysis([['0.1', 0]]);
        Queue::fake();

        $this->patchJson(route('samples.lookup.dilutions.update', $this->parameters($analysis)), ['dilutions' => $dilutions])
            ->assertUnprocessable()
            ->assertJsonPath('errors.dilutions.0', $message);

        $this->assertSame(['0.1'], Result::query()->where('sa_id', $analysis->id)->pluck('df')->all());
        Queue::assertNothingPushed();
    }

    public static function invalidCurves(): array
    {
        return [
            'gap in the curve' => [[['value' => '0.1'], ['value' => '0.001']], 'Verdunningen moeten op elkaar aansluiten, bijvoorbeeld -1, -2 en -3.'],
            'not a decade' => [[['value' => '0.5']], 'Verdunningen moeten machten van tien zijn (1, 0.1, 0.01, ...).'],
            'concentrating' => [[['value' => '10']], 'Verdunningen moeten machten van tien zijn (1, 0.1, 0.01, ...).'],
            'nothing selected' => [[], 'Kies minimaal één verdunning.'],
        ];
    }

    public function test_analyses_without_dilutions_or_replicates_cannot_be_changed(): void
    {
        $this->operator();
        $analysis = $this->analysis([['1', 0]]);
        $analysis->assayRecord->update(['dillution' => 0, 'replicates' => 0]);

        $this->patchJson(route('samples.lookup.dilutions.update', $this->parameters($analysis)), [
            'dilutions' => [['value' => '1', 'replicates' => 2]],
        ])->assertUnprocessable()->assertJsonValidationErrors('analysis');

        $this->assertSame(1, Result::query()->where('sa_id', $analysis->id)->count());
    }

    #[DataProvider('frozenProjects')]
    public function test_frozen_projects_reject_dilution_changes_with_423(string $field): void
    {
        $this->operator();
        $analysis = $this->analysis([['0.1', 0]]);
        $analysis->projectRecord->update([$field => 1]);
        Queue::fake();

        $this->patchJson(route('samples.lookup.dilutions.update', $this->parameters($analysis)), [
            'dilutions' => [['value' => '0.1', 'replicates' => 0], ['value' => '0.01', 'replicates' => 0]],
        ])->assertStatus(423);

        $this->assertSame(1, Result::query()->where('sa_id', $analysis->id)->count());
        Queue::assertNothingPushed();
    }

    public static function frozenProjects(): array
    {
        return ['locked' => ['locked'], 'authorized' => ['auth_status']];
    }

    public function test_an_analysis_of_another_sample_is_not_found(): void
    {
        $this->operator();
        $analysis = $this->analysis([['0.1', 0]]);
        $other = $this->analysis([['0.1', 0]]);

        $this->getJson(route('samples.lookup.dilutions', ['sample' => $other->sample, 'analysis' => $analysis->id]))->assertNotFound();
    }

    private function operator(bool $permitted = true): void
    {
        $this->actingAs(User::factory()->create(['enabled' => 1]));
        Gate::define('samples.view', fn () => true);
        Gate::define('dilutions.update', fn () => $permitted);
    }

    private function parameters(SampleAnalysis $analysis): array
    {
        return ['sample' => $analysis->sample, 'analysis' => $analysis->id];
    }

    /**
     * @param  list<array{0: string, 1: int}>  $rows  df and replicate of each result row
     */
    private function analysis(array $rows): SampleAnalysis
    {
        $project = Project::create(['client' => 1, 'subclient' => 0, 'project_name' => 'Dilution project']);
        $sample = Sample::create([
            'barcode' => 'DIL'.$project->id, 'follow_no' => 1, 'description' => 'Dilution sample', 'sampling_method' => 1,
            'date_registered' => '1788998400', 'registered_by' => 1, 'client' => 1, 'subclient' => 0,
            'project' => $project->id, 'custom_fields' => '{}', 'predicted_end' => 1789171200,
            'sample_innoculated' => '', 'analyses_data' => '[]',
        ]);
        $assay = Assay::create([
            'original_id' => 1, 'name' => 'Dilution assay', 'type_base' => 1, 'media_id' => '[]',
            'dillution' => 1, 'replicates' => 1, 'confirmation' => 0, 'confirmation_script' => '{}',
            'type' => 1, 'meta_assays' => '', 'max_count' => 300, 'min_count' => 10, 'script' => '',
            'custom_fields' => '{}', 'duration' => '48', 'start_from' => '', 'article_code' => '',
        ]);
        $analysis = $sample->analyses()->create([
            'profile_group' => 1, 'follow_number' => 1, 'profile' => 0, 'assay' => $assay->id,
            'assay_base' => $assay->id, 'predicted_end' => 1789171200, 'project' => $project->id,
            'is_ready' => true, 'storedResult' => ['output' => ['result' => 'old']],
        ]);

        foreach ($rows as $index => [$df, $replicate]) {
            Result::create([
                'sample' => $sample->id, 'sa_id' => $analysis->id, 'follow_no' => $index + 1, 'profile' => 0,
                'assay' => $assay->id, 'assay_base' => $assay->id, 'roaming_id' => 0,
                'df' => $df, 'rep' => $replicate, 'data' => (object) [],
            ]);
        }

        return $analysis;
    }
}
