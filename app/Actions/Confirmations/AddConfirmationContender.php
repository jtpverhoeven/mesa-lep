<?php

namespace App\Actions\Confirmations;

use App\Confirmations\AssayConfirmationConfiguration;
use App\Models\Confirmation;
use App\Models\SampleAnalysis;

class AddConfirmationContender
{
    public function __construct(
        private ConfirmationMutation $mutation,
        private RecalculateConfirmation $recalculate,
    ) {}

    public function handle(SampleAnalysis $analysis, string $df, int $rep): SampleAnalysis
    {
        return $this->mutation->execute($analysis, function (SampleAnalysis $analysis, ?Confirmation $confirmation) use ($df, $rep): SampleAnalysis {
            $confirmation = $this->mutation->requireConfirmation($confirmation);
            $this->mutation->assertScope($analysis, $df, $rep);
            $steps = AssayConfirmationConfiguration::decode($analysis->assayRecord?->confirmation_script);
            $track = is_array($confirmation->racetrack) ? $confirmation->racetrack : [];
            $contenders = $track[$df][$rep] ?? [];
            $contenders[] = array_fill(0, count($steps), '');
            $track[$df][$rep] = $contenders;
            $confirmation->racetrack = $track;
            $this->recalculate->handle($analysis, $confirmation, true);
            $this->mutation->invalidate($analysis);

            return $analysis;
        });
    }
}
