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

class PhCalculationTest extends TestCase
{
    /**
     * @param  array<string, string>  $measurements
     */
    #[DataProvider('legacyResults')]
    public function test_reports_legacy_ph_average(array $measurements, string $result, bool $ready): void
    {
        $analysis = new SampleAnalysis(['conf_requested' => 0]);
        $analysis->id = 10;
        $analysis->setRelation('results', collect([new Result(['df' => '1', 'rep' => 0, 'data' => $measurements])]));
        $assay = new Assay(['script' => 'file:ph', 'replicates' => 0, 'confirmation' => 0]);
        $context = new ResultCalculationContext($analysis, $assay, new RoamingAnalysis(['dillutions' => [1], 'replicates' => 0]));

        $calculation = app(ResultCalculationResolver::class)->resolve($assay)->calculate($context);

        $this->assertSame(['kve' => $result], $calculation['output']);
        $this->assertSame($ready, $calculation['isReady']);
    }

    public static function legacyResults(): array
    {
        return [
            'averages with decimal comma' => [['kve' => '7,1', 'meting2' => '7.4'], '7,25', true],
            'single measurement' => [['kve' => '6'], '6,00', true],
            'above pH 14' => [['kve' => '14,1', 'meting2' => '14'], 'Fout resultaat gevonden', false],
            'spread above 0.5' => [['kve' => '7', 'meting2' => '7,6'], 'Resultaten lagen meer dan 0.5 af van elkaar', false],
            'no measurements' => [[], 'Geen resultaten gevonden', false],
        ];
    }
}
