<?php

namespace Tests\Unit;

use App\Calculations\Exceptions\ResultCalculatorNotConfiguredException;
use App\Calculations\ResultCalculationResolver;
use App\Events\AnalysisResultCalculationFailed;
use App\Models\Assay;
use Tests\TestCase;

class ResultCalculationFailureTest extends TestCase
{
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
