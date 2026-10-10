<?php

namespace App\Actions\Confirmations;

use App\ChangeTracking\ChangeTracker;
use App\Confirmations\AssayConfirmationConfiguration;
use App\Models\Confirmation;
use App\Models\Media;
use App\Models\SampleAnalysis;

class UpdateConfirmationTrackValue
{
    public function __construct(
        private ConfirmationMutation $mutation,
        private RecalculateConfirmation $recalculate,
        private ChangeTracker $changeTracker,
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

            $previous = $track[$df][$rep][$contender][$step];
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

            $medium = Media::query()->find($steps[$step]['mediaId'] ?? null);
            $this->changeTracker->changed(14, project: $analysis->project, sample: $analysis->sample, said: $analysis->id,
                event: 'Bevestiging aangepast: '.$analysis->sampleRecord?->barcode.', analyse: '.$analysis->assayRecord?->name
                    .', test:'.$medium?->short_name.' verdunning: '.$df.', replica: '.$rep.', kolonie:'.($contender + 1),
                from: $previous ?? false, to: $value);

            return $analysis;
        });
    }
}
