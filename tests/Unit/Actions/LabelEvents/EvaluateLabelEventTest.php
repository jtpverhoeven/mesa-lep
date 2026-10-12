<?php

namespace Tests\Unit\Actions\LabelEvents;

use App\Actions\LabelEvents\EvaluateLabelEvent;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class EvaluateLabelEventTest extends TestCase
{
    #[DataProvider('conditions')]
    public function test_conditions_and_negation_match_legacy_contexts(array $event, array $sample, array $analysis, array $assay, bool $expected): void
    {
        $matches = (new EvaluateLabelEvent)->handle($event, $sample, $analysis, $assay);

        $this->assertSame($expected, $matches);
    }

    #[DataProvider('invalidEvents')]
    public function test_invalid_rules_do_not_print_even_when_negated(array $event): void
    {
        $matches = (new EvaluateLabelEvent)->handle($event);

        $this->assertFalse($matches);
    }

    public function test_any_selected_assay_can_match_the_current_or_original_assay(): void
    {
        $event = ['event' => 'L', 'event_group' => '2', 'event_data' => ['type' => 'assayIs', 'inspect_value' => '12, 34,56', 'negative' => '0']];
        $evaluator = new EvaluateLabelEvent;

        $this->assertTrue($evaluator->handle($event, [], ['assay_base' => 34]));
        $this->assertTrue($evaluator->handle($event, [], ['assay_base' => 99, 'original_assay_base' => 56]));
        $this->assertFalse($evaluator->handle($event, [], ['assay_base' => 3, 'original_assay_base' => 4]));
    }

    public function test_missing_sample_fields_do_not_equal_empty_values(): void
    {
        $event = ['event' => 'L', 'event_group' => 1, 'event_data' => ['type' => 'sField', 'inspect' => 'matrix', 'inspect_value' => '', 'negative' => '0']];
        $evaluator = new EvaluateLabelEvent;

        $this->assertFalse($evaluator->handle($event, ['custom_fields' => '{}']));
        $this->assertTrue($evaluator->handle($event, ['custom_fields' => ['matrix' => '']]));
    }

    public static function conditions(): array
    {
        $definitions = [
            'sample field' => [1, ['type' => 'sField', 'inspect' => 'matrix', 'inspect_value' => 'Water'], ['custom_fields' => '{"matrix":"Water"}'], [], []],
            'client' => [1, ['type' => 'sClient', 'inspect_value' => '12'], ['client' => 12], [], []],
            'method' => [1, ['type' => 'mMethod', 'inspect_value' => '7'], ['sampling_method' => 7], [], []],
            'sample type' => [1, ['type' => 'mType', 'inspect_value' => 'R'], ['sample_type' => 'R'], [], []],
            'assay' => [2, ['type' => 'assayIs', 'inspect_value' => '24'], [], ['assay_base' => 24], []],
            'assay type' => [2, ['type' => 'assayTypeIs', 'inspect_value' => '3'], [], [], ['type_base' => 3]],
        ];
        $cases = [];

        foreach ($definitions as $name => [$group, $condition, $sample, $analysis, $assay]) {
            foreach (['0', '1'] as $negative) {
                $event = ['event' => 'L', 'event_group' => (string) $group, 'event_data' => json_encode([...$condition, 'negative' => $negative])];
                $cases[$name.' matching, negative '.$negative] = [$event, $sample, $analysis, $assay, $negative === '0'];
                $cases[$name.' missing, negative '.$negative] = [$event, [], [], [], $negative === '1'];
            }
        }

        foreach ([1, 2, 3] as $group) {
            $cases['unconditional group '.$group] = [['event' => 'P', 'event_group' => $group, 'event_data' => 'stale'], [], [], [], true];
        }

        return $cases;
    }

    public static function invalidEvents(): array
    {
        $condition = ['type' => 'sClient', 'inspect_value' => '12', 'negative' => '1'];

        return [
            'unknown group' => [['event' => 'P', 'event_group' => 4]],
            'non-scalar group' => [['event' => 'P', 'event_group' => [1]]],
            'unknown event' => [['event' => 'X', 'event_group' => 1]],
            'conditional THT' => [['event' => 'L', 'event_group' => 3, 'event_data' => $condition]],
            'invalid JSON' => [['event' => 'L', 'event_group' => 1, 'event_data' => '{broken']],
            'no condition' => [['event' => 'L', 'event_group' => 1, 'event_data' => null]],
            'unknown condition' => [['event' => 'L', 'event_group' => 1, 'event_data' => [...$condition, 'type' => 'unknown']]],
            'wrong group' => [['event' => 'L', 'event_group' => 2, 'event_data' => $condition]],
            'invalid negation' => [['event' => 'L', 'event_group' => 1, 'event_data' => [...$condition, 'negative' => 'yes']]],
            'non-scalar negation' => [['event' => 'L', 'event_group' => 1, 'event_data' => [...$condition, 'negative' => ['1']]]],
            'missing field to inspect' => [['event' => 'L', 'event_group' => 1, 'event_data' => [...$condition, 'type' => 'sField']]],
            'non-scalar value' => [['event' => 'L', 'event_group' => 1, 'event_data' => [...$condition, 'inspect_value' => ['12']]]],
        ];
    }
}
