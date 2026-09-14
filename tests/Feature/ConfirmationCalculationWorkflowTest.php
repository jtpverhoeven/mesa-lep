<?php

namespace Tests\Feature;

use App\Actions\Results\CalculateAnalysisResult;
use App\Actions\Results\UpdateResultValue;
use App\Calculations\Contracts\ResultCalculation;
use App\Calculations\ResultCalculationContext;
use App\Models\Assay;
use App\Models\AssayProfile;
use App\Models\Confirmation;
use App\Models\Result;
use App\Models\RoamingAnalysis;
use App\Models\SampleAnalysis;
use Illuminate\Support\Facades\DB;
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
