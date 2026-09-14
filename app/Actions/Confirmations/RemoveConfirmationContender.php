<?php

namespace App\Actions\Confirmations;

use App\Models\Confirmation;
use App\Models\SampleAnalysis;

class RemoveConfirmationContender
{
    public function __construct(
        private ConfirmationMutation $mutation,
        private RecalculateConfirmation $recalculate,
    ) {}

    public function handle(SampleAnalysis $analysis, string $df, int $rep, int $contender): SampleAnalysis
    {
        return $this->mutation->execute($analysis, function (SampleAnalysis $analysis, ?Confirmation $confirmation) use ($df, $rep, $contender): SampleAnalysis {
            $confirmation = $this->mutation->requireConfirmation($confirmation);
            $this->mutation->assertScope($analysis, $df, $rep);
            $track = is_array($confirmation->racetrack) ? $confirmation->racetrack : [];
            unset($track[$df][$rep][$contender]);
            $track[$df][$rep] = array_values($track[$df][$rep] ?? []);
            $confirmation->racetrack = $track;
            $this->recalculate->handle($analysis, $confirmation, true);
            $this->mutation->invalidate($analysis);

            return $analysis;
        });
    }
}
