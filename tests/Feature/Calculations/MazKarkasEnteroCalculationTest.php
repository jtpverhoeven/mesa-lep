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

class MazKarkasEnteroCalculationTest extends TestCase
{
    /**
     * @param  list<array{string, int, string}>  $rows
     */
    #[DataProvider('legacyResults')]
    public function test_reports_legacy_log_count_and_classification(array $rows, int $replicates, string $script, string $result, string $addendum): void
    {
        $calculation = $this->calculate($this->context($rows, $replicates, $script));

        $this->assertSame(['kve' => $result, 'addendum' => $addendum], $calculation['output']);
        $this->assertTrue($calculation['isReady']);
    }

    public static function legacyResults(): array
    {
        return [
            'replicates' => [[['1', 0, '12'], ['1', 1, '14']], 1, 'file:mazKarkasEntero', '1.81', '(Acceptabel)'],
            'replicates with pig limits' => [[['1', 0, '12'], ['1', 1, '14']], 1, "\$varkens=True\r\nfile:mazKarkasEntero", '1.81', '(Toereikend)'],
            'replicates below minimum are indicative' => [[['1', 0, '5'], ['1', 1, '6']], 1, 'file:mazKarkasEntero', '1.44<sup>*</sup>', '(Toereikend)'],
            'replicates all zero' => [[['1', 0, '0'], ['1', 1, '0']], 1, 'file:mazKarkasEntero', '0.00', '(Toereikend)'],
            'replicates above maximum count as maximum' => [[['1', 0, '>'], ['1', 1, '>']], 1, 'file:mazKarkasEntero', '2.88<sup>*</sup>', '(Ontoereikend)'],
            'dilutions sum two plates unweighted' => [[['1', 0, '150'], ['0.1', 0, '14']], 0, 'file:mazKarkasEntero', '2.91', '(Ontoereikend)'],
            'dilutions below minimum are not indicative' => [[['1', 0, '5'], ['0.1', 0, '0']], 0, 'file:mazKarkasEntero', '1.40', '(Toereikend)'],
        ];
    }

    public function test_rejects_inconsistent_replicates(): void
    {
        $calculation = $this->calculate($this->context([['1', 0, '10'], ['1', 1, '60']]));

        $this->assertSame(['kve' => 'Fout resultaat gevonden'], $calculation['output']);
        $this->assertFalse($calculation['isReady']);
    }

    public function test_applies_per_replicate_confirmation_ratios(): void
    {
        $context = $this->context([['1', 0, '40'], ['1', 1, '50']])->withConfirmation(new ConfirmationSnapshot(
            'enabled_complete', 1, 'per_plate', true, ['1' => ['0' => 0.5, '1' => 0.5]], [], [], 10,
        ));

        $this->assertSame('2.05', $this->calculate($context)['output']['kve']);
    }

    private function calculate(ResultCalculationContext $context): array
    {
        return app(ResultCalculationResolver::class)->resolve($context->assay)->calculate($context);
    }

    /**
     * @param  list<array{string, int, string}>  $rows
     */
    private function context(array $rows, int $replicates = 1, string $script = 'file:mazKarkasEntero'): ResultCalculationContext
    {
        $analysis = new SampleAnalysis(['conf_requested' => 0]);
        $analysis->id = 10;
        $analysis->setRelation('results', collect(array_map(
            fn (array $row): Result => new Result(['df' => $row[0], 'rep' => $row[1], 'data' => ['kve' => $row[2]]]),
            $rows,
        )));
        $assay = new Assay(['script' => $script, 'replicates' => $replicates, 'confirmation' => 0, 'min_count' => 10, 'max_count' => 150]);

        return new ResultCalculationContext($analysis, $assay, new RoamingAnalysis(['dillutions' => [1], 'replicates' => $replicates]));
    }
}
