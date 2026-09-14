<?php

namespace App\Actions\Confirmations;

use App\Models\Confirmation;
use App\Models\SampleAnalysis;

class SetConfirmationDecision
{
    public function __construct(
        private ConfirmationMutation $mutation,
        private RecalculateConfirmation $recalculate,
    ) {}

    public function handle(SampleAnalysis $analysis, string $decision): SampleAnalysis
    {
        return $this->mutation->execute($analysis, function (SampleAnalysis $analysis, ?Confirmation $confirmation) use ($decision): SampleAnalysis {
            if ($decision === 'enable') {
                $analysis->conf_requested = 1;
                $analysis->save();
                $confirmation ??= new Confirmation(['said' => $analysis->id]);
                $this->recalculate->handle($analysis, $confirmation, true);
            } else {
                $analysis->conf_requested = $decision === 'disable' ? 2 : 0;
                $confirmation?->delete();
                $analysis->save();
            }

            $this->mutation->invalidate($analysis);

            return $analysis;
        });
    }
}
