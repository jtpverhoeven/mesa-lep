<?php

namespace Tests\Feature\Calculations;

use App\Calculations\ResultCalculationContext;
use App\Calculations\ResultCalculationResolver;
use App\Confirmations\ConfirmationSnapshot;
use App\Models\Assay;
use App\Models\Result;
use App\Models\RoamingAnalysis;
use App\Models\SampleAnalysis;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class MazKarkasKiemCalculationTest extends TestCase
{
    /**
     * @param  array<string, string>  $counts
     */
    #[DataProvider('legacyResults')]
    public function test_reports_legacy_log_count_and_classification(array $counts, string $script, string $result, string $addendum): void
    {
        $context = $this->context($counts, $script);

        $calculation = app(ResultCalculationResolver::class)->resolve($context->assay)->calculate($context);

        $this->assertSame(['kve' => $result, 'addendum' => $addendum], $calculation['output']);
        $this->assertTrue($calculation['isReady']);
    }

    public static function legacyResults(): array
    {
        return [
            'two plates' => [['0.1' => '150', '0.01' => '14'], 'file:mazKarkasKiem', '3.87', '(Acceptabel)'],
            'pig limits' => [['0.1' => '150', '0.01' => '14'], "\$varkens=True\r\nfile:mazKarkasKiem", '3.87', '(Toereikend)'],
            'above upper limit' => [['0.001' => '120', '0.0001' => '12'], 'file:mazKarkasKiem', '5.78', '(Ontoereikend)'],
            'below minimum is indicative' => [['1' => '5', '0.1' => '0'], 'file:mazKarkasKiem', '1.40<sup>*</sup>', '(Toereikend)'],
            'all zero estimates minimum' => [['1' => '0', '0.1' => '0'], 'file:mazKarkasKiem', '0.70<sup>*</sup>', '(Toereikend)'],
            'all above maximum estimates maximum' => [['1' => '>', '0.1' => '400'], 'file:mazKarkasKiem', '4.30<sup>*</sup>', '(Acceptabel)'],
        ];
    }

    public function test_rejects_a_rising_dilution_curve(): void
    {
        $calculation = app(ResultCalculationResolver::class)
            ->resolve(($context = $this->context(['1' => '10', '0.1' => '50']))->assay)
            ->calculate($context);

        $this->assertSame(['kve' => 'Fout resultaat gevonden'], $calculation['output']);
        $this->assertFalse($calculation['isReady']);
    }

    public function test_applies_per_plate_confirmation_ratios(): void
    {
        $context = $this->context(['0.1' => '150', '0.01' => '14'])->withConfirmation(new ConfirmationSnapshot(
            'enabled_complete', 1, 'per_plate', true, ['0.1' => ['0' => 0.5], '0.01' => ['0' => 0.5]], [], [], 10,
        ));

        $calculation = app(ResultCalculationResolver::class)->resolve($context->assay)->calculate($context);

        $this->assertSame('3.57', $calculation['output']['kve']);
    }

    /**
     * @param  array<string, string>  $counts
     */
    private function context(array $counts, string $script = 'file:mazKarkasKiem'): ResultCalculationContext
    {
        $analysis = new SampleAnalysis(['conf_requested' => 0]);
        $analysis->id = 10;
        $analysis->setRelation('results', collect(array_map(
            fn (string $dilution, string $count): Result => new Result(['df' => $dilution, 'rep' => 0, 'data' => ['kve' => $count]]),
            array_keys($counts),
            $counts,
        )));
        $assay = new Assay(['script' => $script, 'replicates' => 0, 'confirmation' => 0, 'min_count' => 10, 'max_count' => 300]);

        return new ResultCalculationContext($analysis, $assay, new RoamingAnalysis(['dillutions' => array_keys($counts), 'replicates' => 0]));
    }
}
