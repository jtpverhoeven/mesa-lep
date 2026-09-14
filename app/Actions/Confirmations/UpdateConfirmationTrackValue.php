<?php

namespace App\Actions\Confirmations;

use App\Confirmations\AssayConfirmationConfiguration;
use App\Models\Confirmation;
use App\Models\SampleAnalysis;

class UpdateConfirmationTrackValue
{
    public function __construct(
        private ConfirmationMutation $mutation,
        private RecalculateConfirmation $recalculate,
    ) {}

    public function handle(SampleAnalysis $analysis, string $df, int $rep, int $contender, int $step, string $value): SampleAnalysis
    {
        return $this->mutation->execute($analysis, function (SampleAnalysis $analysis, ?Confirmation $confirmation) use ($df, $rep, $contender, $step, $value): SampleAnalysis {
            $confirmation = $this->mutation->requireConfirmation($confirmation);
            $this->mutation->assertScope($analysis, $df, $rep);
            $steps = AssayConfirmationConfiguration::decode($analysis->assayRecord?->confirmation_script);
            $track = is_array($confirmation->racetrack) ? $confirmation->racetrack : [];

            if (! isset($track[$df][$rep][$contender]) || ! array_key_exists($step, $track[$df][$rep][$contender])) {
                abort(422, 'Deze bevestigingsstap bestaat niet.');
            }

            $track[$df][$rep][$contender][$step] = $value;
            $disposition = (string) ($steps[$step]['disposition'] ?? '');

            if ($disposition !== '?' && $value !== $disposition) {
                for ($downstream = $step + 1; $downstream < count($steps); $downstream++) {
                    $track[$df][$rep][$contender][$downstream] = null;
                }
            }

            $confirmation->racetrack = $track;
            $this->recalculate->handle($analysis, $confirmation, true);
            $this->mutation->invalidate($analysis);

            return $analysis;
        });
    }
}
