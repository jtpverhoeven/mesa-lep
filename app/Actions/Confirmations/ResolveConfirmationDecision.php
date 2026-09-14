<?php

namespace App\Actions\Confirmations;

use App\Calculations\ResultCalculationContext;
use App\Confirmations\ConfirmationSnapshot as ConfirmationWorkflowSnapshot;
use App\Confirmations\ConfirmationTrigger;
use App\Models\AssayProfile;
use App\Models\Confirmation;
use App\Models\SampleAnalysis;

class ResolveConfirmationDecision
{
    public function __construct(private RecalculateConfirmation $recalculate) {}

    public function handle(SampleAnalysis $analysis, ResultCalculationContext $context, array $baseCalculation): array
    {
        $assay = $context->assay;
        $trigger = ConfirmationTrigger::fromCalculation($baseCalculation, $context);
        $baseReady = (bool) ($baseCalculation['isReady'] ?? false);

        if ((int) $assay->confirmation !== 1 || ! $baseReady || ! $trigger->eligible) {
            return $this->result('not_applicable', (int) $analysis->conf_requested, false, null, $trigger);
        }

        $decision = (int) $analysis->conf_requested;

        if ($decision === 0) {
            $automaticDecision = $this->automaticDecision($assay, $context, $trigger);

            if ($automaticDecision === null) {
                return $this->result('decision_pending', 0, true, null, $trigger);
            }

            $decision = $automaticDecision;
            $analysis->conf_requested = $decision;
            $analysis->save();
        }

        if ($decision === 2) {
            Confirmation::query()->where('said', $analysis->id)->delete();
            $analysis->setRelation('confirmationRecord', null);

            return $this->result('disabled', 2, false, null, $trigger);
        }

        $confirmation = Confirmation::query()->where('said', $analysis->id)->first();
        $confirmation ??= new Confirmation(['said' => $analysis->id]);
        $this->recalculate->handle($analysis, $confirmation, true);
        $confirmation = $confirmation->fresh();
        $analysis->setRelation('confirmationRecord', $confirmation);
        $status = $confirmation->isReady ? 'enabled_complete' : 'enabled_pending';
        $snapshot = ConfirmationWorkflowSnapshot::from($analysis, $assay, $confirmation, $status, $trigger->targetAnalysisId);

        return $this->result($status, 1, false, $snapshot, $trigger);
    }

    private function automaticDecision($assay, ResultCalculationContext $context, ConfirmationTrigger $trigger): ?int
    {
        $initiation = (int) $assay->confirmation_init;
        $confTrip = $context->settings instanceof AssayProfile ? (int) $context->settings->conf_trip : 0;

        if ($confTrip > 0) {
            return $trigger->numericValue !== null && $trigger->numericValue > $confTrip ? 1 : 2;
        }

        return match ($initiation) {
            1 => 1,
            2 => 2,
            default => null,
        };
    }

    private function result(
        string $status,
        int $decision,
        bool $decisionRequired,
        ?ConfirmationWorkflowSnapshot $snapshot,
        ConfirmationTrigger $trigger,
    ): array {
        return [
            'status' => $status,
            'decision' => $decision,
            'decisionRequired' => $decisionRequired,
            'snapshot' => $snapshot,
            'trigger' => $trigger,
        ];
    }
}
