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

class MazSchelpCalculationTest extends TestCase
{
    /**
     * @param  list<array{string, int, string}>  $rows
     */
    #[DataProvider('legacyResults')]
    public function test_reports_legacy_shellfish_count(array $rows, array $assay, string $result, array $disposition, array $messages): void
    {
        $calculation = $this->calculate($this->context($rows, $assay));

        $this->assertSame(['kve' => $result], $calculation['output']);
        $this->assertSame($disposition, $calculation['disposition']);
        $this->assertSame($messages, $calculation['messageBag']);
        $this->assertTrue($calculation['isReady']);
    }

    public static function legacyResults(): array
    {
        return [
            'replicates' => [[['1', 0, '13'], ['1', 1, '14']], [], '540', [], []],
            'replicates below minimum' => [[['1', 0, '3'], ['1', 1, '4']], [], '140<sup>*</sup>', [], []],
            'replicates all zero' => [[['1', 0, '0'], ['1', 1, '0']], [], '<20', ['kve' => '-'], []],
            'replicates awaiting confirmation' => [[['1', 0, '13'], ['1', 1, '14']], ['confirmation' => 1], '540<sup>**</sup>', [], []],
            'dilutions warn about deviating plates' => [[['1', 0, '100'], ['0.1', 0, '30']], ['replicates' => 0], '120', [], ['Verdacht resultaat, verschil te groot tussen verdunning (1) en (0.1)']],
        ];
    }

    public function test_applies_confirmation_ratios_to_replicates(): void
    {
        $context = $this->context([['1', 0, '13'], ['1', 1, '14']], ['confirmation' => 1, 'confirmation_type' => 0])
            ->withConfirmation(new ConfirmationSnapshot('enabled_complete', 1, 'global', true, ['global' => ['0' => 0.5]], [], [], 10));

        $this->assertSame(['kve' => '270'], $this->calculate($context)['output']);
    }

    private function calculate(ResultCalculationContext $context): array
    {
        return app(ResultCalculationResolver::class)->resolve($context->assay)->calculate($context);
    }

    /**
     * @param  list<array{string, int, string}>  $rows
     */
    private function context(array $rows, array $assay = []): ResultCalculationContext
    {
        $analysis = new SampleAnalysis(['conf_requested' => 0]);
        $analysis->id = 10;
        $analysis->setRelation('results', collect(array_map(
            fn (array $row): Result => new Result(['df' => $row[0], 'rep' => $row[1], 'data' => ['kve' => $row[2]]]),
            $rows,
        )));
        $assay = new Assay([
            'script' => 'file:mazSchelp',
            'replicates' => 1,
            'confirmation' => 0,
            'min_count' => 10,
            'max_count' => 150,
            ...$assay,
        ]);

        return new ResultCalculationContext($analysis, $assay, new RoamingAnalysis(['dillutions' => [1], 'replicates' => $assay->replicates]));
    }
}
