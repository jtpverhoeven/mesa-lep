<?php

namespace Tests\Feature\Calculations;

use App\Calculations\ResultCalculationContext;
use App\Calculations\ResultCalculationResolver;
use App\Models\Assay;
use App\Models\Result;
use App\Models\RoamingAnalysis;
use App\Models\SampleAnalysis;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class MazLuchtCalculationTest extends TestCase
{
    #[DataProvider('legacyResults')]
    public function test_reports_legacy_air_measurement(string $variable, string $count, string $result): void
    {
        $context = $this->context($variable, $count);

        $calculation = app(ResultCalculationResolver::class)->resolve($context->assay)->calculate($context);

        $this->assertSame(['kve' => $result], $calculation['output']);
        $this->assertTrue($calculation['isReady']);
    }

    public static function legacyResults(): array
    {
        return [
            'air sampler applies Feller' => ['$airsampler=True', '21', '220'],
            'air sampler at maximum' => ['$airsampler=True', '150', '1.880'],
            'air sampler above maximum is indicative' => ['$airsampler=True', '160', '2.040<sup>*</sup>'],
            'air sampler beyond Feller table' => ['$airsampler=True', '401', '4.010<sup>*</sup>'],
            'air sampler overflow counts as maximum' => ['$airsampler=True', '>', '1.880<sup>*</sup>'],
            'sedimentation' => ['$sedimentation=True', '100', '1,59'],
            'no variable reports the count' => ['', '12', '12'],
        ];
    }

    public function test_rejects_text_for_an_air_sampler(): void
    {
        $context = $this->context('$airsampler=True', 'n.v.t.');

        $calculation = app(ResultCalculationResolver::class)->resolve($context->assay)->calculate($context);

        $this->assertSame(['kve' => 'Fout resultaat gevonden'], $calculation['output']);
        $this->assertFalse($calculation['isReady']);
    }

    private function context(string $variable, string $count): ResultCalculationContext
    {
        $analysis = new SampleAnalysis(['conf_requested' => 0]);
        $analysis->id = 10;
        $analysis->setRelation('results', collect([new Result(['df' => '1', 'rep' => 0, 'data' => ['kve' => $count]])]));
        $assay = new Assay([
            'script' => trim($variable."\r\nfile:mazLucht"),
            'replicates' => 0,
            'confirmation' => 0,
            'min_count' => 0,
            'max_count' => 150,
        ]);

        return new ResultCalculationContext($analysis, $assay, new RoamingAnalysis(['dillutions' => [1], 'replicates' => 0]));
    }
}
