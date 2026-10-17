<?php

namespace Tests\Feature\Calculations;

use App\Calculations\Maz7218GrensCalculation;
use App\Calculations\ResultCalculationContext;
use App\Calculations\ResultCalculationResolver;
use App\Confirmations\ConfirmationSnapshot;
use App\Models\Assay;
use App\Models\Confirmation;
use App\Models\Project;
use App\Models\Result;
use App\Models\RoamingAnalysis;
use App\Models\SampleAnalysis;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Fixtures\LegacyMaz7218Grens;
use Tests\TestCase;

class Maz7218GrensCalculationTest extends TestCase
{
    #[DataProvider('borderResults')]
    public function test_reports_legacy_presence_and_absence_values(string $value, string $dutch, string $english, string $disposition): void
    {
        $context = $this->context($value);

        $calculation = app(ResultCalculationResolver::class)->resolve($context->assay)->calculate($context);

        $this->assertSame(['kve' => $dutch], $calculation['output']);
        $this->assertSame(['kve' => $english], $calculation['outputEn']);
        $this->assertSame(['kve' => $disposition], $calculation['disposition']);
        $this->assertSame('kve', $calculation['reportIn']);
        $this->assertTrue($calculation['isReady']);
    }

    public static function borderResults(): array
    {
        return [
            'plus' => ['+', 'Aangetoond', 'Detected', '+'],
            'one' => ['1', 'Aangetoond', 'Detected', '+'],
            'overflow' => ['>', 'Aangetoond', 'Detected', '+'],
            'Dutch yes' => ['J', 'Aangetoond', 'Detected', '+'],
            'English yes' => ['Y', 'Aangetoond', 'Detected', '+'],
            'minus' => ['-', 'Niet aangetoond', 'Not detected', '-'],
            'zero' => ['0', 'Niet aangetoond', 'Not detected', '-'],
            'no' => ['N', 'Niet aangetoond', 'Not detected', '-'],
        ];
    }

    #[DataProvider('reportOptions')]
    public function test_preserves_legacy_report_overrides(string $value, array $variables, array $dutch, string $english, string $disposition): void
    {
        $context = $this->context($value, $variables);

        $calculation = app(Maz7218GrensCalculation::class)->calculate($context);

        $this->assertSame($dutch, $calculation['output']);
        $this->assertSame(['kve' => $english], $calculation['outputEn']);
        $this->assertSame(['kve' => $disposition], $calculation['disposition']);
        $this->assertTrue($calculation['isReady']);
    }

    public static function reportOptions(): array
    {
        return [
            'PCR detected' => ['+', ['analyseIsPCR' => 'true'], ['kve' => 'Aangetoond'], 'Detected', '+'],
            'PCR suspected' => ['+', ['analyseIsPCR' => 'yes', 'bijPositiefVerdachtWeergevenOpRapport' => 'on'], ['kve' => 'Verdacht'], 'Suspected', '+'],
            'PCR hidden' => ['+', ['analyseIsPCR' => '1', 'bijPositiefNietOpRapport' => 'true'], ['kve' => '', 'kve ' => 'Verdacht'], '', '+'],
            'suspected precedes hidden' => ['+', ['analyseIsPCR' => 'true', 'bijPositiefNietOpRapport' => 'true', 'bijPositiefVerdachtWeergevenOpRapport' => 'true'], ['kve' => 'Verdacht'], 'Suspected', '+'],
            'PCR negative ignores display rules' => ['-', ['analyseIsPCR' => 'true', 'bijPositiefNietOpRapport' => 'true'], ['kve' => 'Niet aangetoond'], 'Not detected', '-'],
            'culture ignores PCR rules' => ['+', ['analyseIsPCR' => 'false', 'bijPositiefNietOpRapport' => 'true', 'bijPositiefVerdachtWeergevenOpRapport' => 'true'], ['kve' => 'Aangetoond'], 'Detected', '+'],
            'positive replacement precedes PCR' => ['+', ['bijPositiefResultaatVervangen' => 'true', 'resultaatVervangenMet' => '{"nl":"Afwijkend","en":"Deviant"}', 'nieuwResultaatDispositie' => '-', 'analyseIsPCR' => 'true', 'bijPositiefNietOpRapport' => 'true'], ['kve' => 'Afwijkend'], 'Deviant', '-'],
            'negative replacement' => ['-', ['bijNegatiefResultaatVervangen' => 'true', 'resultaatVervangenMet' => '{"nl":"Voldoet","en":"Compliant"}', 'nieuwResultaatDispositie' => '+'], ['kve' => 'Voldoet'], 'Compliant', '+'],
            'replacement preserves disposition by default' => ['+', ['bijPositiefResultaatVervangen' => '1', 'resultaatVervangenMet' => '{"nl":"Anders","en":"Other"}'], ['kve' => 'Anders'], 'Other', '+'],
            'missing replacement becomes blank' => ['+', ['bijPositiefResultaatVervangen' => 'true'], ['kve' => ''], '', '+'],
            'invalid replacement becomes blank' => ['-', ['bijNegatiefResultaatVervangen' => 'true', 'resultaatVervangenMet' => 'invalid'], ['kve' => ''], '', '-'],
            'missing translation becomes blank' => ['+', ['bijPositiefResultaatVervangen' => 'true', 'resultaatVervangenMet' => '{"nl":"Anders"}'], ['kve' => 'Anders'], '', '+'],
            'false replacement remains inactive' => ['+', ['bijPositiefResultaatVervangen' => 'false', 'resultaatVervangenMet' => '{"nl":"Anders"}'], ['kve' => 'Aangetoond'], 'Detected', '+'],
            'negative does not use positive replacement' => ['-', ['bijPositiefResultaatVervangen' => 'true', 'resultaatVervangenMet' => '{"nl":"Anders"}'], ['kve' => 'Niet aangetoond'], 'Not detected', '-'],
        ];
    }

    #[DataProvider('confirmedResults')]
    public function test_uses_existing_confirmation_snapshots(int $type, array $ratios, string $dutch, string $english, string $disposition, bool $ready): void
    {
        $context = $this->context('+');
        $context->assay->confirmation = 1;
        $context->assay->confirmation_type = $type;
        $context->analysis->conf_requested = 1;
        $context = $context->withConfirmation(new ConfirmationSnapshot('enabled_complete', 1, $type === 0 ? 'global' : 'per_plate', true, $ratios, [], [], 10));

        $calculation = app(Maz7218GrensCalculation::class)->calculate($context);

        $this->assertSame($dutch, $calculation['output']['kve']);
        $this->assertSame($english, $calculation['outputEn']['kve']);
        $this->assertSame(['kve' => $disposition], $calculation['disposition']);
        $this->assertSame($ready, $calculation['isReady']);
    }

    public static function confirmedResults(): array
    {
        return [
            'per plate positive fraction' => [1, [1 => [0 => 0.25]], 'Aangetoond', 'Detected', '+', true],
            'per plate zero' => [1, [1 => [0 => 0.0]], 'Niet aangetoond', 'Not detected', '-', true],
            'global positive fraction' => [0, ['global' => [0 => 0.25]], 'Aangetoond', 'Detected', '+', true],
            'global zero' => [0, ['global' => [0 => 0.0]], 'Niet aangetoond', 'Not detected', '-', true],
            'missing scope' => [0, [], '', '', '+', false],
            'per plate uses fixed dilution one' => [1, ['0.1' => [0 => 1.0]], 'Niet aangetoond', 'Not detected', '-', true],
        ];
    }

    #[DataProvider('skippedConfirmationResults')]
    public function test_preserves_the_skipped_confirmation_marker(string $value, array $variables, string $dutch, string $english): void
    {
        $context = $this->context($value, $variables);
        $context->assay->confirmation = 1;
        $context->analysis->conf_requested = 2;

        $calculation = app(Maz7218GrensCalculation::class)->calculate($context);

        $this->assertSame($dutch, $calculation['output']['kve']);
        $this->assertSame($english, $calculation['outputEn']['kve']);
        $this->assertTrue($calculation['isReady']);
    }

    public static function skippedConfirmationResults(): array
    {
        return [
            'culture' => ['+', [], 'Aangetoond <sup>**</sup>', 'Detected <sup>**</sup>'],
            'PCR has no marker' => ['+', ['analyseIsPCR' => 'true'], 'Aangetoond', 'Detected'],
            'negative has no marker' => ['-', [], 'Niet aangetoond', 'Not detected'],
            'replacement has no marker' => ['+', ['bijPositiefResultaatVervangen' => 'true', 'resultaatVervangenMet' => '{"nl":"Anders","en":"Other"}'], 'Anders', 'Other'],
        ];
    }

    public function test_positive_messages_precede_replaced_dispositions_and_are_suppressed_after_authorisation(): void
    {
        $context = $this->context('+', ['toonMeldingIndienPositief' => 'Controle nodig', 'bijPositiefResultaatVervangen' => 'true', 'nieuwResultaatDispositie' => '-']);
        $context->analysis->setRelation('projectRecord', new Project(['auth_status' => 0]));

        $calculation = app(Maz7218GrensCalculation::class)->calculate($context);

        $this->assertSame(['Controle nodig'], $calculation['messageBag']);
        $this->assertSame(['kve' => '-'], $calculation['disposition']);

        $context->analysis->projectRecord->auth_status = 1;
        $authorised = app(Maz7218GrensCalculation::class)->calculate($context);

        $this->assertSame([], $authorised['messageBag']);
    }

    public function test_hidden_pcr_uses_the_custom_report_label_including_the_legacy_trailing_space(): void
    {
        $context = $this->context('+', ['analyseIsPCR' => 'true', 'bijPositiefNietOpRapport' => 'true']);
        $context->assay->custom_fields = '{"resultin":"Aanwezigheid"}';

        $calculation = app(Maz7218GrensCalculation::class)->calculate($context);

        $this->assertSame(['kve' => '', 'Aanwezigheid ' => 'Verdacht'], $calculation['output']);
        $this->assertSame(['kve' => 'Aanwezigheid'], $calculation['resultMask']);
    }

    #[DataProvider('emptyCtValues')]
    public function test_allows_empty_ct_only_for_minus_and_zero_results(string $value, bool $ready): void
    {
        $context = $this->context($value);
        $context->analysis->results->first()->data = ['kve' => $value, 'Ctwaarde' => ''];

        $calculation = app(Maz7218GrensCalculation::class)->calculate($context);

        $this->assertSame($ready, $calculation['isReady']);
        $this->assertSame($ready ? 'Niet aangetoond' : 'Niet afgerond', $calculation['output']['kve']);
    }

    public static function emptyCtValues(): array
    {
        return ['minus' => ['-', true], 'zero' => ['0', true], 'positive' => ['+', false], 'N is not exempt' => ['N', false]];
    }

    public function test_requested_confirmation_keeps_the_report_incomplete_and_preserves_the_message(): void
    {
        $context = $this->context('+', ['toonMeldingIndienPositief' => 'Controle nodig']);
        $context->assay->confirmation = 1;
        $context->analysis->conf_requested = 1;

        $calculation = app(Maz7218GrensCalculation::class)->calculate($context);

        $this->assertSame(['kve' => 'Niet afgerond'], $calculation['output']);
        $this->assertSame(['kve' => 'Not completed'], $calculation['outputEn']);
        $this->assertSame(['kve' => ''], $calculation['disposition']);
        $this->assertSame(['Controle nodig'], $calculation['messageBag']);
        $this->assertFalse($calculation['isReady']);
        $this->assertTrue($calculation['calculationReady']);
        $this->assertTrue($calculation['confirmationTrigger']['eligible']);
    }

    #[DataProvider('legacyParityCases')]
    public function test_report_matches_the_legacy_implementation(string $value, array $variables, array $assaySettings, int $decision, ?array $ratios, bool $confirmationReady = true): void
    {
        $context = $this->context($value, $variables);
        $context->assay->fill($assaySettings);
        $context->analysis->conf_requested = $decision;

        if ($ratios !== null) {
            $context = $context->withConfirmation(new ConfirmationSnapshot($confirmationReady ? 'enabled_complete' : 'enabled_pending', $decision, (int) $context->assay->confirmation_type === 0 ? 'global' : 'per_plate', $confirmationReady, $ratios, [], [], 10));
        }

        $expected = (new LegacyMaz7218Grens($context, $variables))->provide();
        $calculation = app(Maz7218GrensCalculation::class)->calculate($context);

        foreach ($expected as $field => $value) {
            $this->assertSame($value, $calculation[$field], $field);
        }
    }

    public static function legacyParityCases(): array
    {
        $cases = [];

        foreach (['+', '1', '>', 'J', 'Y', '-', '0', 'N', '<', 'NVT', '1.0', '0.0', 'unknown', ''] as $value) {
            $cases['plain '.$value] = [$value, [], [], 0, null];
            $cases['per plate pending '.$value] = [$value, [], ['confirmation' => 1, 'confirmation_type' => 1], 1, null];
            $cases['global pending '.$value] = [$value, [], ['confirmation' => 1, 'confirmation_type' => 0], 1, null];
        }

        foreach (self::reportOptions() as $name => [$value, $variables]) {
            $cases[$name] = [$value, $variables, [], 0, null];
        }

        $cases['per plate missing ratio'] = ['+', [], ['confirmation' => 1, 'confirmation_type' => 1], 1, []];
        $cases['global missing ratio'] = ['+', [], ['confirmation' => 1, 'confirmation_type' => 0], 1, []];
        $cases['per plate zero'] = ['+', [], ['confirmation' => 1, 'confirmation_type' => 1], 1, [1 => [0 => 0]]];
        $cases['global zero'] = ['+', [], ['confirmation' => 1, 'confirmation_type' => 0], 1, ['global' => [0 => 0]]];
        $cases['replicates remain unsupported'] = ['+', [], ['replicates' => 1], 0, null];
        $cases['decline bypasses replicates'] = ['+', [], ['replicates' => 1, 'confirmation' => 1], 2, null];
        $cases['per plate partial positive'] = ['+', [], ['confirmation' => 1, 'confirmation_type' => 1], 1, [1 => [0 => 1]], false];
        $cases['per plate partial negative'] = ['+', [], ['confirmation' => 1, 'confirmation_type' => 1], 1, [1 => [0 => 0]], false];
        $cases['global partial positive'] = ['+', [], ['confirmation' => 1, 'confirmation_type' => 0], 1, ['global' => [0 => 1]], false];
        $cases['global partial negative'] = ['+', [], ['confirmation' => 1, 'confirmation_type' => 0], 1, ['global' => [0 => 0]], false];

        return $cases;
    }

    public function test_partial_confirmation_ratios_are_read_from_the_loaded_record_before_the_workflow_finishes(): void
    {
        $context = $this->context('+', ['toonMeldingIndienPositief' => 'Controle nodig']);
        $context->assay->confirmation = 1;
        $context->assay->confirmation_type = 1;
        $context->analysis->conf_requested = 1;
        $context->analysis->setRelation('confirmationRecord', new Confirmation([
            'isReady' => false,
            'metadata' => [1 => [0 => ['ratio' => 1]]],
        ]));

        $calculation = app(Maz7218GrensCalculation::class)->calculate($context);

        $this->assertSame(['kve' => 'Niet afgerond'], $calculation['output']);
        $this->assertSame(['Controle nodig'], $calculation['messageBag']);
        $this->assertFalse($calculation['isReady']);
    }

    private function context(string $value, array $variables = []): ResultCalculationContext
    {
        $analysis = new SampleAnalysis(['conf_requested' => 0]);
        $analysis->id = 10;
        $analysis->setRelation('results', collect([new Result(['df' => '1', 'rep' => 0, 'data' => ['kve' => $value]])]));
        $assay = new Assay(['script' => 'file: maz7218grens', 'replicates' => 0, 'confirmation' => 0]);

        foreach ($variables as $name => $setting) {
            $assay->script = '$'.$name.'='.$setting."\n".$assay->script;
        }

        return new ResultCalculationContext($analysis, $assay, new RoamingAnalysis(['dillutions' => [1], 'replicates' => 0]));
    }
}
