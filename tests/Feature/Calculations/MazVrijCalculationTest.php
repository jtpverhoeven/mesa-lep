<?php

namespace Tests\Feature\Calculations;

use App\Calculations\ResultCalculationContext;
use App\Calculations\ResultCalculationResolver;
use App\Models\Assay;
use App\Models\Result;
use App\Models\RoamingAnalysis;
use App\Models\SampleAnalysis;
use Tests\TestCase;

class MazVrijCalculationTest extends TestCase
{
    public function test_reports_the_entry_as_entered(): void
    {
        $analysis = new SampleAnalysis(['conf_requested' => 0]);
        $analysis->id = 10;
        $analysis->setRelation('results', collect([new Result(['df' => '1', 'rep' => 0, 'data' => ['kve' => 'Verzonden naar extern lab']])]));
        $assay = new Assay(['script' => 'file:mazVrij', 'replicates' => 0, 'confirmation' => 0, 'custom_fields' => '{"resultin":"Status"}']);
        $context = new ResultCalculationContext($analysis, $assay, new RoamingAnalysis(['dillutions' => [1], 'replicates' => 0]));

        $calculation = app(ResultCalculationResolver::class)->resolve($assay)->calculate($context);

        $this->assertSame(['kve' => 'Verzonden naar extern lab'], $calculation['output']);
        $this->assertSame(['kve' => 'Status'], $calculation['resultMask']);
        $this->assertTrue($calculation['isReady']);
    }
}
