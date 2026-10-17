<?php

namespace Tests\Feature;

use App\Calculations\MazLegionella2019Calculation;
use App\Calculations\ResultCalculationContext;
use App\Confirmations\ConfirmationSnapshot;
use App\Models\Assay;
use App\Models\Confirmation;
use App\Models\RoamingAnalysis;
use App\Models\Sample;
use App\Models\SampleAnalysis;
use PHPUnit\Framework\Attributes\DataProvider;
use ReflectionClass;
use Tests\TestCase;

class LegionellaCalculationTest extends TestCase
{
    #[DataProvider('matrixResults')]
    public function test_matches_legacy_matrix_arithmetic_and_report_formatting(
        string $matrix,
        array $counts,
        float $ratio,
        string $volume,
        string $expected,
        bool $includeParent = false,
    ): void {
        $context = $this->context($matrix, $counts, $ratio, $volume, includeParent: $includeParent);
        require_once base_path('legacy/app/shared/calculation.class.php');
        require_once base_path('legacy/app/private/templateScripts/mazLegionella2019.class.php');
        $reflection = new ReflectionClass('mazLegionella2019');
        $legacy = $reflection->newInstanceWithoutConstructor();
        $reflection->getProperty('metaResults')->setValue($legacy, $context->metaResults);
        $reflection->getProperty('originalAssayId')->setValue($legacy, 900);
        $reflection->getProperty('sampleInfo')->setValue($legacy, ['sample_extra' => json_encode(['filter_volume' => $volume])]);
        $legacyValue = $reflection->getMethod('calculateEndResult')->invoke($legacy, $matrix, $ratio);

        $result = app(MazLegionella2019Calculation::class)->calculate($context);

        $this->assertSame((float) $legacyValue, (float) $result['confirmationTrigger']['numericValue']);
        $this->assertSame($expected, $result['output']['kve']);
        $this->assertSame($expected, $result['outputEn']['kve']);
        $this->assertTrue($result['isReady']);
    }

    public static function matrixResults(): array
    {
        return [
            'A highest treatment' => ['A', [[4], [6], [0], [2], [9], [1], [3]], 1.0, '250', '900'],
            'A negative BCYE does not trigger the positive-control error' => ['A', [[9], [0], [-1], [0], [0], [0], [0]], 1.0, '250', '900'],
            'A compares arrays before taking first plate' => ['A', [[1, 2], [7], [0], [3], [4], [5], [6]], 1.0, '250', '100'],
            'A does not select highest individual replicate' => ['A', [[2, 999], [9, 0], [0], [3, 0], [4, 0], [5, 0], [6, 0]], 1.0, '250', '900'],
            'A fractional confirmation is not rounded to colonies' => ['A', [[3], [1], [0], [0], [0], [0], [0]], 0.5, '250', '150'],
            'A thousands grouping' => ['A', [[123], [0], [0], [0], [0], [0], [0]], 1.0, '250', '12.000'],
            'B maximum sum rather than maximum plate' => ['B', [[2, 4], [3, 5], [1, 1]], 1.0, '250', '160'],
            'B fractional confirmation' => ['B', [[1, 2], [1, 1], [0, 0]], 0.5, '250', '30'],
            'B decimal comma volume' => ['B', [[2, 3], [0], [0]], 1.0, '125,5', '200'],
            'B truncates the summed count before confirmation' => ['B', [['1.7', '2.7'], [0], [0]], 0.5, '250', '40'],
            'C sums first treatment only' => ['C', [[2, 3], [900]], 1.0, '250', '20'],
            'C retains skipped parent position' => ['C', [[9]], 1.0, '250', '<4', true],
            'C fractional confirmation' => ['C', [[1, 1, 1]], 0.5, '250', '6'],
            'C rounds below 100 to one significant figure' => ['C', [[17]], 1.0, '250', '70'],
            'C rounds from 100 to two significant figures' => ['C', [[28]], 1.0, '250', '110'],
            'zero confirmation ratio reports not detected' => ['C', [[12]], 0.0, '250', '<4'],
        ];
    }

    #[DataProvider('speciesLabels')]
    public function test_reports_each_legacy_species_combination(array $tracks, string $expected): void
    {
        $context = $this->context('C', [[25]], tracks: $tracks);

        $result = app(MazLegionella2019Calculation::class)->calculate($context);

        $this->assertSame($expected, $result['output']['aanvulling']);
        $this->assertSame($expected, $result['outputEn']['aanvulling']);
        $this->assertTrue($result['hidden']['detected']);
        $this->assertSame('+', $result['disposition']['kve']);
    }

    public static function speciesLabels(): array
    {
        return [
            'type 1' => [[['+', '-', '+', '-', '-']], "Legionella pneumophila \u{2013} serotype 1"],
            'type 2-14' => [[['+', '-', '-', '+', '-']], "Legionella pneumophila \u{2013} serotype 2-14"],
            'non-pneumophila' => [[['+', '-', '-', '-', '+']], 'Legionella spp. (non-pneumophila)'],
            'both pneumophila types' => [[['+', '-', '+', '+', '-']], "Legionella pneumophila \u{2013} serotype 1 & serotype 2-14"],
            'type 1 and non-pneumophila' => [[['+', '-', '+', '-', '+']], "Legionella pneumophila \u{2013} serotype 1 & Legionella spp. (non-pneumophila)"],
            'type 2-14 and non-pneumophila' => [[['+', '-', '-', '+', '+']], "Legionella pneumophila \u{2013} serotype 2-14 & Legionella spp. (non-pneumophila)"],
            'all species' => [[['+', '-', '+', '+', '+']], "Legionella pneumophila \u{2013} serotype 1, 2-14 & Legionella spp. (non-pneumophila)"],
            'species across different colonies' => [[['+', '-', '+', '-', '-'], ['+', '-', '-', '+', '+']], "Legionella pneumophila \u{2013} serotype 1, 2-14 & Legionella spp. (non-pneumophila)"],
        ];
    }

    public function test_waits_for_confirmation_without_a_global_ratio(): void
    {
        $context = $this->context('C', [[3]], ratio: null);

        $result = app(MazLegionella2019Calculation::class)->calculate($context);

        $this->assertSame(['kve' => 'Bevestiging wacht'], $result['output']);
        $this->assertSame(['kve' => '+'], $result['disposition']);
        $this->assertTrue($result['calculationReady']);
        $this->assertTrue($result['askForMetaConfirmation']);
        $this->assertTrue($result['confirmationTrigger']['eligible']);
        $this->assertFalse($result['isReady']);
    }

    public function test_reports_incomplete_when_a_ratio_exists_but_confirmation_is_unfinished(): void
    {
        $context = $this->context('C', [[3]]);
        $context->analysis->confirmationRecord->isReady = false;
        $context = $context->withConfirmation(null);

        $result = app(MazLegionella2019Calculation::class)->calculate($context);

        $this->assertSame(['kve' => 'Niet afgerond'], $result['output']);
        $this->assertSame(['kve' => 'Not completed'], $result['outputEn']);
        $this->assertFalse($result['isReady']);
    }

    public function test_rejects_positive_bcye_for_matrix_a_before_requesting_confirmation(): void
    {
        $context = $this->context('A', [[5], [4], [0, 1], [0], [0], [0], [0]], ratio: null);

        $result = app(MazLegionella2019Calculation::class)->calculate($context);

        $this->assertSame('Fout resultaat gevonden, BCYE - Positief', $result['output']['kve']);
        $this->assertFalse($result['isReady']);
        $this->assertFalse($result['confirmationTrigger']['eligible']);
    }

    #[DataProvider('missingCounts')]
    public function test_keeps_the_parent_incomplete_when_a_child_count_is_missing(array $counts): void
    {
        $context = $this->context('C', [$counts]);

        $result = app(MazLegionella2019Calculation::class)->calculate($context);

        $this->assertSame('Niet afgerond', $result['output']['kve']);
        $this->assertFalse($result['calculationReady']);
        $this->assertFalse($result['isReady']);
    }

    public static function missingCounts(): array
    {
        return ['empty field' => [['']], 'null field' => [[null]], 'no plates' => [[]]];
    }

    public function test_uses_the_legacy_default_filter_volume_when_the_sample_has_no_volume(): void
    {
        $context = $this->context('C', [[0]], ratio: null);
        $context->analysis->sampleRecord->sample_extra = '{}';

        $result = app(MazLegionella2019Calculation::class)->calculate($context);

        $this->assertSame('<4', $result['output']['kve']);
        $this->assertTrue($result['isReady']);
    }

    public function test_uses_custom_volume_for_the_matrix_c_detection_limit(): void
    {
        $context = $this->context('C', [[0]], ratio: null, volume: '125,5');

        $result = app(MazLegionella2019Calculation::class)->calculate($context);

        $this->assertSame('<8', $result['output']['kve']);
        $this->assertFalse($result['hidden']['detected']);
    }

    #[DataProvider('invalidVolumes')]
    public function test_does_not_report_a_result_for_an_invalid_filter_volume(string $volume): void
    {
        $context = $this->context('C', [[3]], volume: $volume);

        $result = app(MazLegionella2019Calculation::class)->calculate($context);

        $this->assertSame('Fout resultaat gevonden', $result['output']['kve']);
        $this->assertFalse($result['isReady']);
        $this->assertFalse($result['confirmationTrigger']['eligible']);
    }

    public static function invalidVolumes(): array
    {
        return ['zero' => ['0'], 'negative' => ['-1'], 'not numeric' => ['unknown']];
    }

    private function context(
        string $matrix,
        array $counts,
        ?float $ratio = 1.0,
        string $volume = '250',
        array $tracks = [['+', '-', '+', '-', '-']],
        bool $includeParent = false,
    ): ResultCalculationContext {
        $assay = new Assay([
            'original_id' => 900,
            'type' => 4,
            'confirmation' => 1,
            'confirmation_type' => 0,
            'script' => "file: mazLegionella2019\n\$matrixType=".$matrix,
            'custom_fields' => '{"resultin":"kve/l"}',
        ]);
        $analysis = new SampleAnalysis(['conf_requested' => $ratio === null ? 0 : 1]);
        $analysis->id = 10;
        $analysis->setRelation('sampleRecord', new Sample(['sample_extra' => json_encode(['filter_volume' => $volume])]));
        $confirmation = $ratio === null ? null : new Confirmation([
            'metadata' => ['global' => [0 => ['ratio' => $ratio]]],
            'racetrack' => ['global' => [0 => $tracks]],
            'isReady' => true,
        ]);
        $analysis->setRelation('confirmationRecord', $confirmation);
        $metaResults = $includeParent ? [900 => ['1' => [['kve' => '']]]] : [];

        foreach ($counts as $index => $plates) {
            $metaResults[$index + 1]['1'] = array_map(fn (mixed $count): array => ['kve' => $count], $plates);
        }

        return new ResultCalculationContext(
            $analysis,
            $assay,
            new RoamingAnalysis(['dillutions' => [], 'replicates' => 0]),
            $ratio === null ? null : new ConfirmationSnapshot(
                'enabled_complete', 1, 'global', true, ['global' => [0 => $ratio]], [], [], 10,
            ),
            $metaResults,
        );
    }
}
