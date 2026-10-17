<?php

namespace Tests\Feature;

use App\Actions\Confirmations\GetConfirmation;
use App\Actions\Confirmations\SetConfirmationDecision;
use App\Actions\Results\CalculateAnalysisResult;
use App\Actions\Results\UpdateResultValue;
use App\Calculations\Contracts\ResultCalculation;
use App\Calculations\ResultCalculationContext;
use App\Events\AnalysisResultCalculated;
use App\Events\ConfirmationDecisionRequired;
use App\Jobs\CalculateAnalysisResultJob;
use App\Models\Assay;
use App\Models\AssayProfile;
use App\Models\Confirmation;
use App\Models\Result;
use App\Models\RoamingAnalysis;
use App\Models\SampleAnalysis;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ConfirmationCalculationWorkflowTest extends TestCase
{
    private mixed $originalDefaultConnection;

    private mixed $originalSqliteDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->originalDefaultConnection = config('database.default');
        $this->originalSqliteDatabase = config('database.connections.sqlite.database');
        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => ':memory:',
            'result-calculations.default' => TestConfirmationCalculation::class,
        ]);
        DB::purge('sqlite');

        foreach (glob(database_path('migrations/*.php')) as $path) {
            (require $path)->up();
        }
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

    public function test_legionella_parent_reports_incomplete_without_child_plate_counts(): void
    {
        [$analysis] = $this->roamingAnalysis(0);
        $analysis->assayRecord->update(['script' => 'file: mazLegionella2019', 'type' => 4]);
        $analysis->roamingAnalysis->update(['dillutions' => []]);
        $analysis->results()->delete();

        $result = app(CalculateAnalysisResult::class)->handle($analysis);

        $this->assertSame('Niet afgerond', $result['output']['kve']);
        $this->assertSame('Not completed', $result['outputEn']['kve']);
        $this->assertFalse($result['isReady']);
        $this->assertSame('not_applicable', $result['confirmation']['status']);
        $this->assertDatabaseCount('confirmations', 0);
    }

    #[DataProvider('legionellaNegativeMatrices')]
    public function test_legionella_parent_reports_legacy_negative_limits_without_confirmation(string $matrix, int $groups, string $expected): void
    {
        [$analysis] = $this->roamingAnalysis(0);
        $analysis->assayRecord->update([
            'original_id' => 900,
            'script' => "file: mazLegionella2019\n\$matrixType=".$matrix,
            'type' => 4,
            'confirmation_type' => 0,
        ]);
        $analysis->roamingAnalysis->update(['dillutions' => []]);
        $analysis->results()->delete();

        for ($index = 0; $index < $groups; $index++) {
            [$child] = $this->roamingAnalysis(0);
            $child->update(['original_assay_base' => $index + 1]);
            $row = $child->results()->firstOrFail();
            $row->update(['data' => ['kve' => '0']]);
        }

        $result = app(CalculateAnalysisResult::class)->handle($analysis);

        $this->assertSame($expected, $result['output']['kve']);
        $this->assertSame('Geen Legionella spp. aangetoond', $result['output']['aanvulling']);
        $this->assertSame('Legionella spp. not detected', $result['outputEn']['aanvulling']);
        $this->assertSame('-', $result['disposition']['kve']);
        $this->assertFalse($result['hidden']['detected']);
        $this->assertTrue($result['isReady']);
        $this->assertSame('not_applicable', $result['confirmation']['status']);
        $this->assertDatabaseCount('confirmations', 0);
    }

    public static function legionellaNegativeMatrices(): array
    {
        return [
            'matrix A' => ['A', 7, '<100'],
            'matrix B' => ['B', 3, '<100'],
            'matrix C' => ['C', 1, '<4'],
        ];
    }

    public function test_legionella_parent_initializes_global_confirmation_from_child_counts(): void
    {
        [$analysis] = $this->roamingAnalysis(1);
        $analysis->assayRecord->update([
            'original_id' => 900,
            'script' => "file: mazLegionella2019\n\$matrixType=C",
            'type' => 4,
            'confirmation_type' => 0,
            'confirmation_script' => json_encode([['mediaId' => 1, 'chainId' => 1, 'disposition' => '+']]),
        ]);
        $analysis->roamingAnalysis->update(['dillutions' => []]);
        $analysis->results()->delete();
        [$child] = $this->roamingAnalysis(0);
        $child->update(['original_assay_base' => 1]);
        $child->results()->firstOrFail()->update(['data' => ['kve' => '3']]);

        $result = app(CalculateAnalysisResult::class)->handle($analysis);

        $this->assertSame('enabled_pending', $result['confirmation']['status']);
        $this->assertSame('Bevestiging wacht', $result['output']['kve']);
        $this->assertFalse($result['isReady']);
        $this->assertSame(1, $analysis->fresh()->conf_requested);
        $this->assertCount(2, $analysis->fresh()->confirmationRecord->racetrack['global'][0]);
        $this->assertDatabaseCount('confirmations', 1);
    }

    public function test_legionella_plate_forwards_the_parent_confirmation_request_without_a_reportable_result(): void
    {
        [$parent, $child] = $this->legionellaPair();

        $result = app(CalculateAnalysisResult::class)->handle($child);

        $this->assertSame([], $result['output']);
        $this->assertFalse($result['reportable']);
        $this->assertTrue($result['isReady']);
        $this->assertSame($parent->id, $result['meta_parent']);
        $this->assertSame('+', $result['disposition']['kve']);
        $this->assertTrue($result['askForMetaParentConfirmation']);
        $this->assertTrue($result['confirmation']['decision_required']);
        $this->assertSame($parent->id, $result['confirmation']['target_analysis_id']);
        $this->assertFalse($parent->fresh()->is_ready);
        $this->assertDatabaseCount('confirmations', 0);
    }

    public function test_legionella_plate_does_not_use_a_parent_from_a_different_profile_group(): void
    {
        [$parent, $child] = $this->legionellaPair();
        $child->update(['profile_group' => 2]);

        $result = app(CalculateAnalysisResult::class)->handle($child);

        $this->assertFalse($result['isReady']);
        $this->assertFalse($result['askForMetaParentConfirmation']);
        $this->assertArrayNotHasKey('meta_parent', $result);
        $this->assertNull($parent->fresh()->storedResult);
    }

    public function test_legionella_plate_confirmation_reads_return_the_parent_global_scope(): void
    {
        [$parent, $child] = $this->legionellaPair();
        app(CalculateAnalysisResult::class)->handle($child);

        $state = app(GetConfirmation::class)->handle($child);

        $this->assertSame($parent->id, $state['analysis_id']);
        $this->assertSame('global', $state['config']['mode']);
        $this->assertSame('decision_pending', $state['status']);
        $this->assertTrue($state['decision_required']);
    }

    public function test_legionella_plate_confirmation_decisions_are_saved_only_on_the_parent(): void
    {
        $this->actingAs(User::factory()->create(['enabled' => 1]));
        Queue::fake([CalculateAnalysisResultJob::class]);
        [$parent, $child] = $this->legionellaPair();
        app(CalculateAnalysisResult::class)->handle($child);

        $updated = app(SetConfirmationDecision::class)->handle($child, 'enable');

        $this->assertSame($parent->id, $updated->id);
        $this->assertSame(1, $parent->fresh()->conf_requested);
        $this->assertSame(0, $child->fresh()->conf_requested);
        $this->assertDatabaseCount('confirmations', 1);
        $this->assertDatabaseHas('confirmations', ['said' => $parent->id]);
        Queue::assertPushed(CalculateAnalysisResultJob::class, fn (CalculateAnalysisResultJob $job): bool => $job->analysisId === $parent->id);
    }

    public function test_editing_a_legionella_plate_invalidates_and_recalculates_its_parent(): void
    {
        $this->actingAs(User::factory()->create(['enabled' => 1]));
        Queue::fake([CalculateAnalysisResultJob::class]);
        [$parent, $child] = $this->legionellaPair();
        app(CalculateAnalysisResult::class)->handle($child);

        app(UpdateResultValue::class)->handle($child, $child->results()->firstOrFail(), 'kve', '0');

        $this->assertNull($parent->fresh()->storedResult);
        $this->assertFalse($parent->fresh()->is_ready);

        $result = app(CalculateAnalysisResult::class)->handle($child);

        $this->assertSame('<4', $parent->fresh()->storedResult['output']['kve']);
        $this->assertSame('-', $result['disposition']['kve']);
        $this->assertFalse($result['askForMetaParentConfirmation']);
        $this->assertTrue($parent->fresh()->is_ready);
        Queue::assertPushed(CalculateAnalysisResultJob::class, fn (CalculateAnalysisResultJob $job): bool => $job->analysisId === $parent->id);
    }

    public function test_parent_confirmation_changes_refresh_a_plates_pending_calculation(): void
    {
        $this->actingAs(User::factory()->create(['enabled' => 1]));
        Queue::fake([CalculateAnalysisResultJob::class]);
        [$parent, $child] = $this->legionellaPair();
        app(CalculateAnalysisResult::class)->handle($child);
        app(SetConfirmationDecision::class)->handle($parent, 'enable');
        app(CalculateAnalysisResult::class)->handle($parent);

        $result = app(CalculateAnalysisResult::class)->handle($child->fresh());

        $this->assertSame('enabled_pending', $result['confirmation']['status']);
        $this->assertSame(1, $result['confirmation']['decision']);
        $this->assertFalse($result['confirmation']['decision_required']);
        $this->assertFalse($result['askForMetaParentConfirmation']);
        $this->assertSame(0, $child->fresh()->conf_requested);
        $this->assertDatabaseCount('confirmations', 1);
    }

    public function test_legionella_plate_jobs_broadcast_the_parent_result_and_confirmation_target(): void
    {
        [$parent, $child] = $this->legionellaPair();
        Event::fake([AnalysisResultCalculated::class, ConfirmationDecisionRequired::class]);

        (new CalculateAnalysisResultJob($child->id))->handle(app(CalculateAnalysisResult::class));

        Event::assertDispatched(AnalysisResultCalculated::class, 2);
        Event::assertDispatched(AnalysisResultCalculated::class, fn (AnalysisResultCalculated $event): bool => $event->analysisId === $parent->id
            && $event->calculation['output']['kve'] === 'Bevestiging wacht');
        Event::assertDispatched(ConfirmationDecisionRequired::class, fn (ConfirmationDecisionRequired $event): bool => $event->analysisId === $parent->id
            && $event->mode === 'global');
    }

    public function test_clearing_a_legionella_plate_recalculates_the_parent_as_incomplete(): void
    {
        $this->actingAs(User::factory()->create(['enabled' => 1]));
        Queue::fake([CalculateAnalysisResultJob::class]);
        [$parent, $child] = $this->legionellaPair();
        app(CalculateAnalysisResult::class)->handle($child);
        Event::fake([AnalysisResultCalculated::class, ConfirmationDecisionRequired::class]);

        app(UpdateResultValue::class)->handle($child, $child->results()->firstOrFail(), 'kve', '');
        (new CalculateAnalysisResultJob($parent->id))->handle(app(CalculateAnalysisResult::class));

        $this->assertSame('Niet afgerond', $parent->fresh()->storedResult['output']['kve']);
        $this->assertFalse($parent->fresh()->is_ready);
        Event::assertNotDispatched(ConfirmationDecisionRequired::class);
        Queue::assertPushed(CalculateAnalysisResultJob::class, fn (CalculateAnalysisResultJob $job): bool => $job->analysisId === $parent->id);
    }

    #[DataProvider('completedLegionellaConfirmations')]
    public function test_completed_legionella_confirmation_reports_the_global_ratio_and_species(
        array $tracks,
        float $ratio,
        string $expected,
        bool $detected,
    ): void {
        [$parent, $child] = $this->legionellaPair();
        $steps = [];
        $metadata = [];

        foreach (['+', '-', '?', '?', '?'] as $index => $disposition) {
            $mediaId = $index + 1;
            $steps[] = ['chainId' => $mediaId, 'mediaId' => $mediaId, 'disposition' => $disposition];
            $metadata[$index] = [$mediaId.'_inzet' => '17-10-2026', $mediaId.'_aflees' => '17-10-2026'];
        }

        $parent->assayRecord->update(['confirmation_script' => json_encode($steps)]);
        $parent->update(['conf_requested' => 1]);
        Confirmation::create([
            'said' => $parent->id,
            'metadata' => ['global' => [0 => $metadata]],
            'racetrack' => ['global' => [0 => $tracks]],
            'isReady' => false,
        ]);

        $result = app(CalculateAnalysisResult::class)->handle($parent);

        $this->assertSame('enabled_complete', $result['confirmation']['status']);
        $this->assertSame($ratio, $result['confirmation']['ratios']['global'][0]);
        $this->assertSame($expected, $result['output']['kve']);
        $this->assertSame($detected, $result['hidden']['detected']);
        $this->assertSame(
            $detected ? "Legionella pneumophila \u{2013} serotype 1" : 'Geen Legionella spp. aangetoond',
            $result['output']['aanvulling'],
        );
        $this->assertTrue($result['isReady']);
        $this->assertDatabaseCount('confirmations', 1);
    }

    public static function completedLegionellaConfirmations(): array
    {
        return [
            'one confirmed and one rejected' => [[['+', '-', '+', '-', '-'], ['-']], 0.5, '6', true],
            'all colonies rejected' => [[['-'], ['-']], 0.0, '<4', false],
        ];
    }

    /** @return array{SampleAnalysis, SampleAnalysis} */
    private function legionellaPair(): array
    {
        [$parent] = $this->roamingAnalysis(0);
        $parent->assayRecord->update([
            'original_id' => 900,
            'script' => "file: mazLegionella2019\n\$matrixType=C",
            'type' => 4,
            'confirmation_type' => 0,
            'confirmation_script' => json_encode([['mediaId' => 1, 'chainId' => 1, 'disposition' => '+']]),
        ]);
        $parent->roamingAnalysis->update(['dillutions' => []]);
        $parent->results()->delete();
        [$child] = $this->roamingAnalysis(0);
        $child->assayRecord->update(['script' => 'file: legionellaPassthrough', 'confirmation' => 0]);
        $child->update(['original_assay_base' => 1]);
        $child->results()->firstOrFail()->update(['data' => ['kve' => '3']]);

        return [$parent, $child];
    }

    public function test_ask_mode_keeps_the_decision_pending_and_broadcast_safe(): void
    {
        [$analysis] = $this->roamingAnalysis(0);

        $result = app(CalculateAnalysisResult::class)->handle($analysis);

        $this->assertSame('decision_pending', $result['confirmation']['status']);
        $this->assertTrue($result['confirmation']['decision_required']);
        $this->assertSame('base', $result['output']['result']);
        $this->assertFalse($result['isReady']);
        $this->assertSame(0, $analysis->fresh()->conf_requested);
        $this->assertDatabaseCount('confirmations', 0);
    }

    public function test_ask_mode_does_not_prompt_when_no_colonies_were_detected(): void
    {
        [$analysis] = $this->roamingAnalysis(0);
        $resultRow = $analysis->results()->firstOrFail();
        $resultRow->data = ['kve' => '0'];
        $resultRow->save();

        $result = app(CalculateAnalysisResult::class)->handle($analysis);

        $this->assertSame('not_applicable', $result['confirmation']['status']);
        $this->assertFalse($result['confirmation']['decision_required']);
        $this->assertSame('<10', $result['output']['result']);
        $this->assertTrue($result['isReady']);
        $this->assertSame(0, $analysis->fresh()->conf_requested);
        $this->assertDatabaseCount('confirmations', 0);
    }

    public function test_changing_a_positive_result_preserves_an_existing_confirmation(): void
    {
        $this->actingAs(User::factory()->create(['enabled' => 1]));
        [$analysis] = $this->roamingAnalysis(0);
        $analysis->update(['conf_requested' => 1]);
        $confirmation = Confirmation::create([
            'said' => $analysis->id,
            'note' => 'Entered by the analyst',
            'racetrack' => ['1' => ['0' => [['positive']]]],
            'data' => ['analyst' => 'existing'],
        ]);
        $resultRow = $analysis->results()->firstOrFail();

        app(UpdateResultValue::class)->handle($analysis->fresh(), $resultRow, 'kve', '2');
        $result = app(CalculateAnalysisResult::class)->handle($analysis->fresh());

        $this->assertSame(1, $analysis->fresh()->conf_requested);
        $this->assertFalse($result['confirmation']['decision_required']);
        $this->assertSame($confirmation->id, $analysis->fresh()->confirmationRecord->id);
        $this->assertSame('Entered by the analyst', $confirmation->fresh()->note);
        $this->assertSame('positive', $confirmation->fresh()->racetrack['1']['0'][0][0]);
        $this->assertArrayNotHasKey(1, $confirmation->fresh()->racetrack['1']['0']);
        $this->assertSame(['analyst' => 'existing'], $confirmation->fresh()->data);
    }

    public function test_always_off_adds_not_confirmed_without_creating_a_row(): void
    {
        [$analysis] = $this->roamingAnalysis(2);

        $result = app(CalculateAnalysisResult::class)->handle($analysis);

        $this->assertSame('disabled', $result['confirmation']['status']);
        $this->assertSame('base', $result['output']['result']);
        $this->assertSame(['code' => 'not_confirmed', 'label' => 'niet bevestigd'], $result['addenda'][0]);
        $this->assertSame(2, $analysis->fresh()->conf_requested);
        $this->assertDatabaseCount('confirmations', 0);
    }

    public function test_always_on_initializes_a_row_and_waits_for_the_racetrack(): void
    {
        [$analysis] = $this->roamingAnalysis(1);

        $result = app(CalculateAnalysisResult::class)->handle($analysis);

        $this->assertSame('enabled_pending', $result['confirmation']['status']);
        $this->assertSame('Bevestiging wacht', $result['output']['result']);
        $this->assertSame(1, $analysis->fresh()->conf_requested);
        $this->assertDatabaseCount('confirmations', 1);
    }

    public function test_profile_threshold_enables_confirmation_above_the_threshold(): void
    {
        [$assay, $analysis] = $this->profileAnalysis(10, 1);
        $resultRow = $analysis->results()->firstOrFail();
        $resultRow->data = ['kve' => '20'];
        $resultRow->save();

        $result = app(CalculateAnalysisResult::class)->handle($analysis);

        $this->assertSame('enabled_pending', $result['confirmation']['status']);
        $this->assertSame(1, $analysis->fresh()->conf_requested);
        $this->assertDatabaseCount('confirmations', 1);
        $this->assertSame($assay->id, $analysis->fresh()->assayRecord->id);
    }

    public function test_profile_threshold_overrides_ask_mode_above_the_threshold(): void
    {
        [, $analysis] = $this->profileAnalysis(10, 0);
        $resultRow = $analysis->results()->firstOrFail();
        $resultRow->data = ['kve' => '20'];
        $resultRow->save();

        $result = app(CalculateAnalysisResult::class)->handle($analysis);

        $this->assertSame('enabled_pending', $result['confirmation']['status']);
        $this->assertFalse($result['confirmation']['decision_required']);
        $this->assertSame(1, $analysis->fresh()->conf_requested);
        $this->assertDatabaseCount('confirmations', 1);
    }

    public function test_profile_threshold_overrides_ask_mode_at_or_below_the_threshold(): void
    {
        [, $analysis] = $this->profileAnalysis(10, 0);

        $result = app(CalculateAnalysisResult::class)->handle($analysis);

        $this->assertSame('disabled', $result['confirmation']['status']);
        $this->assertFalse($result['confirmation']['decision_required']);
        $this->assertSame(2, $analysis->fresh()->conf_requested);
        $this->assertDatabaseCount('confirmations', 0);
    }

    /** @return array{SampleAnalysis} */
    private function roamingAnalysis(int $initiation): array
    {
        $assay = Assay::create($this->assayAttributes($initiation));
        $analysis = SampleAnalysis::create([
            'profile_group' => 1,
            'sample' => 10,
            'follow_number' => 1,
            'profile' => 0,
            'assay' => 0,
            'assay_base' => $assay->id,
            'roaming_id' => null,
            'predicted_end' => 0,
            'original_assay_base' => null,
            'conf_requested' => 0,
            'is_ready' => 0,
            'project' => null,
            'project_order' => 1,
            'storedResult' => null,
        ]);
        $roaming = RoamingAnalysis::create([
            'said' => $analysis->id,
            'assay' => $assay->id,
            'dillutions' => [1],
            'replicates' => 0,
            'reference' => [],
            'reference_scope' => '',
            'reference_source' => null,
        ]);
        $analysis->update(['roaming_id' => $roaming->id]);
        $this->resultRow($analysis, $assay, 0);

        return [$analysis];
    }

    /** @return array{Assay, SampleAnalysis} */
    private function profileAnalysis(int $threshold, int $initiation): array
    {
        $assay = Assay::create($this->assayAttributes($initiation));
        $profile = AssayProfile::create([
            'research_profile' => 1,
            'assay' => $assay->id,
            'dillutions' => [1],
            'replicates' => 0,
            'reference' => [],
            'hidden' => 0,
            'project_order' => 1,
            'conf_trip' => $threshold,
            'reference_source' => null,
        ]);
        $analysis = SampleAnalysis::create([
            'profile_group' => 1,
            'sample' => 10,
            'follow_number' => 1,
            'profile' => 1,
            'assay' => $profile->id,
            'assay_base' => $assay->id,
            'roaming_id' => null,
            'predicted_end' => 0,
            'original_assay_base' => null,
            'conf_requested' => 0,
            'is_ready' => 0,
            'project' => null,
            'project_order' => 1,
            'storedResult' => null,
        ]);
        $this->resultRow($analysis, $assay, $profile->id);

        return [$assay, $analysis];
    }

    private function resultRow(SampleAnalysis $analysis, Assay $assay, int $assayId): void
    {
        Result::create([
            'sample' => $analysis->sample,
            'sa_id' => $analysis->id,
            'follow_no' => 1,
            'profile' => $analysis->profile,
            'assay' => $assayId,
            'assay_base' => $assay->id,
            'roaming_id' => $analysis->roaming_id ?? 0,
            'df' => '1',
            'rep' => 0,
            'data' => ['kve' => '1'],
        ]);
    }

    private function assayAttributes(int $initiation): array
    {
        return [
            'original_id' => 0,
            'name' => 'Workflow assay',
            'type_base' => 1,
            'media_id' => '[]',
            'dillution' => 1,
            'replicates' => 0,
            'confirmation' => 1,
            'confirmation_type' => 1,
            'confirmation_script' => '[]',
            'confirmation_support' => null,
            'type' => 1,
            'meta_assays' => '',
            'max_count' => 300,
            'min_count' => 0,
            'script' => '',
            'custom_fields' => '{}',
            'duration' => '1',
            'start_from' => 'r',
            'confirmation_init' => $initiation,
            'confirmation_depth' => 2,
            'article_code' => '',
        ];
    }
}

class TestConfirmationCalculation implements ResultCalculation
{
    public function calculate(ResultCalculationContext $context): array
    {
        $numericValue = (float) ($context->analysis->results->first()?->data['kve'] ?? 0);
        $eligible = $numericValue > 0;

        return [
            'output' => ['result' => $eligible ? 'base' : '<10'],
            'messageBag' => [],
            'reportIn' => 'result',
            'outputEn' => [],
            'disposition' => [],
            'isReady' => true,
            'resultMask' => ['result' => 'result'],
            'resultHide' => [],
            'confirmationTrigger' => [
                'eligible' => $eligible,
                'numericValue' => $numericValue,
                'disposition' => $eligible ? '+' : '-',
                'targetAnalysisId' => $context->analysis->id,
            ],
        ];
    }
}
