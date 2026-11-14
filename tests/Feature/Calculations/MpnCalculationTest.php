<?php

namespace Tests\Feature\Calculations;

use App\Calculations\MpnCalculation;
use App\Calculations\ResultCalculationContext;
use App\Calculations\ResultCalculationResolver;
use App\Models\Assay;
use App\Models\Result;
use App\Models\RoamingAnalysis;
use App\Models\SampleAnalysis;
use Tests\TestCase;

class MpnCalculationTest extends TestCase
{
    public function test_reports_the_entered_mpn_with_thousands_separator(): void
    {
        $this->assertSame(['kve' => '2.400'], $this->calculate('2400')['output']);
        $this->assertSame(['kve' => '<1'], $this->calculate('<1')['output']);
    }

    public function test_a_negative_result_does_not_need_an_mpn_value(): void
    {
        $calculator = app(MpnCalculation::class);

        $this->assertTrue($calculator->allowsEmptyResultField(['kve' => '-', 'MPNwaarde' => ''], 'MPNwaarde'));
        $this->assertFalse($calculator->allowsEmptyResultField(['kve' => '12', 'MPNwaarde' => ''], 'MPNwaarde'));
    }

    private function calculate(string $value): array
    {
        $analysis = new SampleAnalysis(['conf_requested' => 0]);
        $analysis->id = 10;
        $analysis->setRelation('results', collect([new Result(['df' => '1', 'rep' => 0, 'data' => ['kve' => $value, 'MPNwaarde' => '']])]));
        $assay = new Assay(['script' => 'file:mpn', 'replicates' => 0, 'confirmation' => 0]);

        return app(ResultCalculationResolver::class)->resolve($assay)->calculate(
            new ResultCalculationContext($analysis, $assay, new RoamingAnalysis(['dillutions' => [1], 'replicates' => 0])),
        );
    }
}
