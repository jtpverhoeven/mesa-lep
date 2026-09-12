<?php

namespace Tests\Feature;

use App\Actions\Results\CalculateAnalysisResult;
use App\Events\AnalysisResultCalculated;
use App\Events\AnalysisResultCalculationFailed;
use App\Jobs\CalculateAnalysisResultJob;
use App\Models\SampleAnalysis;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Mockery;
use RuntimeException;
use Tests\TestCase;

class CalculateAnalysisResultJobTest extends TestCase
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

    public function test_unexpected_calculation_errors_broadcast_a_failure_event(): void
    {
        $analysis = SampleAnalysis::create([
            'profile_group' => 1,
            'sample' => 10,
            'follow_number' => 1,
            'profile' => 0,
            'assay' => 20,
            'assay_base' => 20,
            'predicted_end' => 0,
            'project' => null,
            'project_order' => 1,
            'is_ready' => false,
            'storedResult' => null,
        ]);
        $calculate = Mockery::mock(CalculateAnalysisResult::class);
        $calculate->shouldReceive('handle')
            ->once()
            ->andThrow(new RuntimeException('invalid dilution curve'));
        Event::fake();

        (new CalculateAnalysisResultJob($analysis->id))->handle($calculate);

        Event::assertDispatched(AnalysisResultCalculationFailed::class, function (AnalysisResultCalculationFailed $event) use ($analysis): bool {
            return $event->sampleId === 10
                && $event->analysisId === $analysis->id
                && $event->message === 'Eindresultaat kon niet worden berekend.';
        });
    }

    public function test_incomplete_calculation_broadcasts_a_non_ready_result(): void
    {
        $analysis = SampleAnalysis::create([
            'profile_group' => 1,
            'sample' => 10,
            'follow_number' => 1,
            'profile' => 0,
            'assay' => 20,
            'assay_base' => 20,
            'predicted_end' => 0,
            'project' => null,
            'project_order' => 1,
            'is_ready' => false,
            'storedResult' => null,
        ]);
        $calculation = [
            'output' => ['result' => 'Niet afgerond'],
            'messageBag' => [],
            'isReady' => false,
        ];
        $calculate = Mockery::mock(CalculateAnalysisResult::class);
        $calculate->shouldReceive('handle')->once()->andReturn($calculation);
        Event::fake();

        (new CalculateAnalysisResultJob($analysis->id))->handle($calculate);

        Event::assertDispatched(AnalysisResultCalculated::class, function (AnalysisResultCalculated $event) use ($analysis, $calculation): bool {
            return $event->sampleId === 10
                && $event->analysisId === $analysis->id
                && $event->calculation === $calculation
                && $event->calculation['isReady'] === false;
        });
    }
}
