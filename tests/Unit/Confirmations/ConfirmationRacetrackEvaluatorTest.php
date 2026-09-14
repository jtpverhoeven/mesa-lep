<?php

namespace Tests\Unit\Confirmations;

use App\Confirmations\ConfirmationRacetrackEvaluator;
use App\Confirmations\PendingConfirmationAssuranceFields;
use Tests\TestCase;

class ConfirmationRacetrackEvaluatorTest extends TestCase
{
    public function test_required_mismatch_finishes_unconfirmed_and_nulls_downstream_steps(): void
    {
        $result = $this->evaluator()->evaluate(
            [
                ['mediaId' => 5, 'chainId' => 1, 'disposition' => '+'],
                ['mediaId' => 12, 'chainId' => 2, 'disposition' => '+'],
            ],
            [['-', '+']],
            [
                '0' => ['5_inzet' => '12-09-2026', '5_aflees' => '13-09-2026'],
            ],
            [],
        );

        $this->assertSame([
            'index' => 0,
            'active' => false,
            'finished' => true,
            'confirmed' => false,
            'visible_steps' => [0],
            'answers' => ['0' => '-', '1' => null],
        ], $result['contenders'][0]);
        $this->assertSame(0, $result['summary']['ratio']);
        $this->assertTrue($result['summary']['scope_ready']);
    }

    public function test_optional_step_requires_an_answer_but_accepts_either_disposition(): void
    {
        $steps = [
            ['mediaId' => 5, 'chainId' => 1, 'disposition' => '+'],
            ['mediaId' => 12, 'chainId' => 2, 'disposition' => '?'],
        ];
        $metadata = [
            '0' => ['5_inzet' => '12-09-2026', '5_aflees' => '13-09-2026'],
            '1' => ['12_inzet' => '12-09-2026', '12_aflees' => '13-09-2026'],
        ];

        $pending = $this->evaluator()->evaluate($steps, [['+', '']], $metadata, []);
        $complete = $this->evaluator()->evaluate($steps, [['+', '-']], $metadata, []);

        $this->assertFalse($pending['summary']['scope_ready']);
        $this->assertSame([0, 1], $pending['contenders'][0]['visible_steps']);
        $this->assertTrue($complete['summary']['scope_ready']);
        $this->assertSame([0, 1], $complete['contenders'][0]['visible_steps']);
        $this->assertTrue($complete['contenders'][0]['confirmed']);
    }

    public function test_assurance_placeholder_is_returned_without_blocking_readiness(): void
    {
        $result = $this->evaluator()->evaluate(
            [['mediaId' => 5, 'chainId' => 1, 'disposition' => '+']],
            [['+']],
            ['0' => ['5_inzet' => '12-09-2026', '5_aflees' => '13-09-2026']],
            ['5' => ['hasDate' => 1]],
        );

        $placeholder = collect($result['metadata_fields'])->firstWhere('key', '5_tht');

        $this->assertSame([
            'key' => '5_tht',
            'kind' => 'assurance_placeholder',
            'available' => false,
            'required' => false,
            'value' => null,
        ], $placeholder);
        $this->assertTrue($result['summary']['scope_ready']);
    }

    private function evaluator(): ConfirmationRacetrackEvaluator
    {
        return new ConfirmationRacetrackEvaluator(new PendingConfirmationAssuranceFields);
    }
}
