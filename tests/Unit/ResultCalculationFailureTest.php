<?php

namespace Tests\Unit;

use App\Calculations\Exceptions\ResultCalculatorNotConfiguredException;
use App\Calculations\Maz7218Calculation;
use App\Calculations\ResultCalculationContext;
use App\Calculations\ResultCalculationResolver;
use App\Calculations\RodacCalculation;
use App\Events\AnalysisResultCalculationFailed;
use App\Models\Assay;
use App\Models\AssayProfile;
use App\Models\Result;
use App\Models\SampleAnalysis;
use Tests\TestCase;

class ResultCalculationFailureTest extends TestCase
{
    public function test_invalid_dilution_curve_returns_a_visible_failure_message(): void
    {
        $analysis = new SampleAnalysis;
        $analysis->setRelation('results', collect([
            new Result(['df' => '10', 'data' => ['kve' => '1000']]),
            new Result(['df' => '1', 'data' => ['kve' => '1000']]),
        ]));
        $assay = new Assay;
        $assay->min_count = 10;
        $assay->max_count = 300;

        $result = app(Maz7218Calculation::class)->calculate(
            new ResultCalculationContext($analysis, $assay, new AssayProfile),
        );

        $this->assertFalse($result['isReady']);
        $this->assertSame(['Onjuiste gegevens in de verdunningscurve.'], $result['messageBag']);
    }

    public function test_rodac_uses_the_rodac_calculator(): void
    {
        $assay = new Assay;
        $assay->id = 1419;
        $assay->script = 'file:rodac';

        $this->assertInstanceOf(
            RodacCalculation::class,
            app(ResultCalculationResolver::class)->resolve($assay),
        );
    }

    public function test_rodac_preserves_the_raw_count_and_marks_over_limit_values(): void
    {
        $analysis = new SampleAnalysis;
        $analysis->setRelation('results', collect([
            new Result(['df' => '1', 'data' => ['kve' => '151']]),
        ]));
        $assay = new Assay;
        $assay->max_count = 150;
        $settings = new AssayProfile;

        $result = app(RodacCalculation::class)->calculate(
            new ResultCalculationContext($analysis, $assay, $settings),
        );

        $this->assertSame('151<sup>*</sup>', $result['output']['kve']);
        $this->assertTrue($result['isReady']);
    }

    public function test_missing_calculator_has_a_frontend_safe_message(): void
    {
        config()->set('result-calculations.calculators', []);

        $assay = new Assay;
        $assay->id = 1419;
        $assay->script = 'file:rodac';

        try {
            app(ResultCalculationResolver::class)->resolve($assay);
            $this->fail('Expected the missing calculator exception to be thrown.');
        } catch (ResultCalculatorNotConfiguredException $exception) {
            $this->assertSame(
                'No result calculator is configured for assay [1419] script [file:rodac].',
                $exception->getMessage(),
            );
            $this->assertSame(
                'Het eindresultaat kan niet worden berekend, omdat er geen rekenmodule is ingesteld voor deze analyse.',
                $exception->userMessage(),
            );
        }
    }

    public function test_failure_event_broadcasts_the_frontend_safe_message(): void
    {
        $exception = new ResultCalculatorNotConfiguredException(1419, 'rodac');
        $event = new AnalysisResultCalculationFailed(10, 20, $exception->userMessage());

        $this->assertSame([
            'sample_id' => 10,
            'analysis_id' => 20,
            'message' => $exception->userMessage(),
        ], $event->broadcastWith());
        $this->assertStringNotContainsString('file:rodac', $event->broadcastWith()['message']);
    }
}
