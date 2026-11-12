<?php

namespace Tests\Unit;

use App\Actions\Results\CheckAnalysisResultsComplete;
use App\Calculations\ResultCalculationContext;
use App\Models\Assay;
use App\Models\AssayProfile;
use App\Models\Result;
use App\Models\SampleAnalysis;
use App\Rules\SequentialDilutions;
use App\Support\DilutionCurve;
use Illuminate\Support\Facades\Validator;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class DilutionCurveTest extends TestCase
{
    #[DataProvider('curves')]
    public function test_a_curve_is_sequential_when_its_decades_are_consecutive(array $dilutions, bool $expected): void
    {
        $this->assertSame($expected, DilutionCurve::isSequential($dilutions));
    }

    public static function curves(): array
    {
        return [
            'empty' => [[], true],
            'single' => [['0.001'], true],
            'from undiluted' => [[1, '0.1', '0.01'], true],
            'unordered' => [['0.001', '0.1', '0.01'], true],
            'duplicate' => [['0.1', '0.1', '0.01'], true],
            'gap' => [['0.1', '0.001'], false],
            'gap from undiluted' => [[1, '0.01'], false],
            'not a decade' => [['0.1', '0.05'], false],
            'not numeric' => [['0.1', 'x'], false],
        ];
    }

    public function test_user_input_must_be_dilutions_not_concentrations(): void
    {
        $this->assertNull(DilutionCurve::problem(['1', '0.1']));
        $this->assertSame(DilutionCurve::NOT_A_DECADE_MESSAGE, DilutionCurve::problem(['10', '1']));
        $this->assertSame(DilutionCurve::NOT_SEQUENTIAL_MESSAGE, DilutionCurve::problem(['0.1', '0.001']));
    }

    public function test_it_formats_the_legacy_text_forms(): void
    {
        $this->assertSame('0.0001', DilutionCurve::format(0.0001));
        $this->assertSame('1', DilutionCurve::format(1.0));
        $this->assertSame('0=1', DilutionCurve::line(1.0));
        $this->assertSame('-3=0.001', DilutionCurve::line(0.001));
    }

    public function test_the_validation_rule_reads_keyed_settings_and_nested_values(): void
    {
        $passes = fn (array $data, array $rules): bool => Validator::make($data, $rules)->passes();

        $this->assertTrue($passes(['d' => ['-1' => '0.1', '-2' => '0.01']], ['d' => [new SequentialDilutions]]));
        $this->assertFalse($passes(['d' => ['-1' => '0.1', '-3' => '0.001']], ['d' => [new SequentialDilutions]]));
        $this->assertFalse($passes(['d' => [['value' => '0.1'], ['value' => '0.001']]], ['d' => [new SequentialDilutions('value')]]));
    }

    public function test_completeness_follows_the_stored_rows_not_the_profile_settings(): void
    {
        $analysis = new SampleAnalysis;
        $context = fn (array $rows): ResultCalculationContext => new ResultCalculationContext(
            tap($analysis, fn (SampleAnalysis $analysis) => $analysis->setRelation('results', collect($rows))),
            new Assay,
            new AssayProfile(['dillutions' => ['-1' => '0.1', '-2' => '0.01', '-3' => '0.001']]),
        );
        $row = fn (string $df, int $rep, array $data): Result => new Result(['df' => $df, 'rep' => $rep, 'data' => $data]);
        $check = new CheckAnalysisResultsComplete;

        $this->assertTrue($check->handle($context([$row('0.1', 0, ['kve' => '12']), $row('0.01', 0, ['kve' => '1'])])));
        $this->assertFalse($check->handle($context([$row('0.1', 0, ['kve' => '12']), $row('0.01', 0, ['kve' => ''])])));
        $this->assertFalse($check->handle($context([$row('0.1', 0, ['kve' => '12']), $row('0.1', 0, ['kve' => '12'])])));
        $this->assertFalse($check->handle($context([])));
    }
}
