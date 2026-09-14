<?php

namespace App\Actions\Confirmations;

use App\Calculations\Contracts\ResultCalculation;
use App\Calculations\ResultCalculationCache;
use App\Calculations\ResultCalculationContext;
use App\Confirmations\ConfirmationSnapshot;

class ApplyConfirmationWorkflowToResult
{
    public function __construct(private ResultCalculationCache $cache) {}

    public function handle(
        ResultCalculationContext $context,
        ?ResultCalculation $calculator,
        array $baseCalculation,
        array $workflow,
    ): array {
        $calculation = $baseCalculation;
        $snapshot = $workflow['snapshot'];

        if ($workflow['status'] === 'enabled_complete' && $calculator !== null && $snapshot instanceof ConfirmationSnapshot) {
            $confirmedContext = $context->withConfirmation($snapshot);
            $calculation = $this->cache->stamp($calculator->calculate($confirmedContext), $confirmedContext, $calculator);
        } elseif ($calculator !== null) {
            $calculation = $this->cache->stamp($calculation, $context, $calculator);
        }

        $calculation['calculationReady'] = (bool) ($baseCalculation['isReady'] ?? false);
        $calculation['confirmation'] = [
            'status' => $workflow['status'],
            'decision' => $workflow['decision'],
            'mode' => $snapshot?->mode ?? ((int) $context->assay->confirmation_type === 0 ? 'global' : 'per_plate'),
            'ready' => $workflow['status'] === 'enabled_complete',
            'decision_required' => $workflow['decisionRequired'],
            'ratios' => $snapshot?->ratios ?? [],
        ];

        if ($workflow['status'] === 'enabled_pending') {
            $calculation = $this->waitingResult($calculation);
        }

        if ($workflow['status'] === 'disabled') {
            $calculation['addenda'] = [
                ...($calculation['addenda'] ?? []),
                ['code' => 'not_confirmed', 'label' => 'niet bevestigd'],
            ];
        }

        $calculation['confirmation']['decision_required'] = $workflow['decisionRequired'];
        $calculation['isReady'] = match ($workflow['status']) {
            'decision_pending', 'enabled_pending' => false,
            default => (bool) ($calculation['isReady'] ?? false),
        };

        return $calculation;
    }

    private function waitingResult(array $calculation): array
    {
        $calculation['output'] = ['result' => 'Bevestiging wacht'];
        $calculation['outputEn'] = ['result' => 'Confirmation pending'];
        $calculation['reportIn'] = 'result';
        $calculation['resultMask'] = ['result' => 'result'];
        $calculation['resultHide'] = [];
        $calculation['disposition'] = [];
        $calculation['messageBag'] = [];
        $calculation['addenda'] = [];

        return $calculation;
    }
}
