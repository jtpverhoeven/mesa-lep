<?php

namespace Tests\Unit\Confirmations;

use App\Actions\Confirmations\ApplyConfirmationWorkflowToResult;
use App\Calculations\Contracts\ResultCalculation;
use App\Calculations\ResultCalculationCache;
use App\Calculations\ResultCalculationContext;
use App\Confirmations\ConfirmationSnapshot;
use App\Confirmations\ConfirmationTrigger;
use App\Models\Assay;
use App\Models\AssayProfile;
use App\Models\SampleAnalysis;
use Tests\TestCase;

class ConfirmationWorkflowTest extends TestCase
{
    public function test_legacy_trigger_fallback_rejects_non_countable_results(): void
    {
        $context = $this->context();

        foreach (['<10', '>300', 'Negative'] as $value) {
            $trigger = ConfirmationTrigger::fromCalculation([
                'output' => ['kve' => $value],
                'reportIn' => 'kve',
                'isReady' => true,
            ], $context);

            $this->assertFalse($trigger->eligible, "[{$value}] should not request confirmation.");
        }
    }

    public function test_pending_decision_keeps_the_unconfirmed_result_visible_but_not_ready(): void
    {
        $result = $this->apply()->handle(
            $this->context(),
            new ConfirmationWorkflowCalculator,
            ['output' => ['kve' => '10'], 'isReady' => true],
            ['status' => 'decision_pending', 'decision' => 0, 'decisionRequired' => true, 'snapshot' => null],
        );

        $this->assertSame('10', $result['output']['kve']);
        $this->assertFalse($result['isReady']);
        $this->assertTrue($result['confirmation']['decision_required']);
    }

    public function test_pending_confirmation_replaces_visible_result_and_readiness(): void
    {
        $result = $this->apply()->handle(
            $this->context(),
            new ConfirmationWorkflowCalculator,
            [
                'output' => ['kve' => '10'],
                'addenda' => [['code' => 'indicative', 'label' => 'indicatieve waarde']],
                'isReady' => true,
            ],
            ['status' => 'enabled_pending', 'decision' => 1, 'decisionRequired' => false, 'snapshot' => null],
        );

        $this->assertSame('Bevestiging wacht', $result['output']['result']);
        $this->assertSame([], $result['addenda']);
        $this->assertFalse($result['isReady']);
        $this->assertSame('enabled_pending', $result['confirmation']['status']);
        $this->assertTrue($result['calculationReady']);
    }

    public function test_disabled_confirmation_keeps_base_result_and_adds_not_confirmed(): void
    {
        $result = $this->apply()->handle(
            $this->context(),
            new ConfirmationWorkflowCalculator,
            ['output' => ['kve' => '10'], 'isReady' => true],
            ['status' => 'disabled', 'decision' => 2, 'decisionRequired' => false, 'snapshot' => null],
        );

        $this->assertSame('10', $result['output']['kve']);
        $this->assertTrue($result['isReady']);
        $this->assertSame(['code' => 'not_confirmed', 'label' => 'niet bevestigd'], $result['addenda'][0]);
    }

    public function test_complete_confirmation_reruns_calculator_with_ratios(): void
    {
        $snapshot = new ConfirmationSnapshot(
            'enabled_complete',
            1,
            'per_plate',
            true,
            ['0.1' => ['0' => 0.5]],
            [5],
            [],
            42,
        );
        $result = $this->apply()->handle(
            $this->context(),
            new ConfirmationWorkflowCalculator,
            ['output' => ['kve' => '10'], 'isReady' => true],
            ['status' => 'enabled_complete', 'decision' => 1, 'decisionRequired' => false, 'snapshot' => $snapshot],
        );

        $this->assertSame('ratio:0.5', $result['output']['kve']);
        $this->assertTrue($result['isReady']);
        $this->assertSame(['0.1' => ['0' => 0.5]], $result['confirmation']['ratios']);
    }

    public function test_cache_key_changes_when_confirmation_decision_changes(): void
    {
        $analysis = new SampleAnalysis(['conf_requested' => 0]);
        $context = new ResultCalculationContext($analysis, new Assay, new AssayProfile);
        $calculator = new ConfirmationWorkflowCalculator;
        $cache = new ResultCalculationCache;
        $stored = $cache->stamp(['isReady' => true], $context, $calculator);

        $analysis->conf_requested = 1;

        $this->assertFalse($cache->isCurrent($stored, $context, $calculator));
    }

    public function test_confirmation_enabled_assay_rejects_cached_result_without_workflow_state(): void
    {
        $context = $this->context();
        $context->assay->confirmation = 1;
        $calculator = new ConfirmationWorkflowCalculator;
        $cache = new ResultCalculationCache;
        $stored = $cache->stamp(['isReady' => true], $context, $calculator);

        $this->assertFalse($cache->isCurrent($stored, $context, $calculator));
    }

    private function apply(): ApplyConfirmationWorkflowToResult
    {
        return new ApplyConfirmationWorkflowToResult(new ResultCalculationCache);
    }

    private function context(): ResultCalculationContext
    {
        return new ResultCalculationContext(new SampleAnalysis, new Assay, new AssayProfile);
    }
}

class ConfirmationWorkflowCalculator implements ResultCalculation
{
    public function calculate(ResultCalculationContext $context): array
    {
        $ratio = $context->confirmation?->ratios['0.1']['0'] ?? null;

        return [
            'output' => ['kve' => $ratio === null ? 'rerun' : 'ratio:'.$ratio],
            'isReady' => true,
            'resultMask' => ['kve' => 'kve'],
            'resultHide' => [],
            'messageBag' => [],
        ];
    }
}
