<?php

namespace App\Actions\Results;

use App\Actions\Confirmations\RecalculateConfirmation;
use App\Actions\Projects\EnsureEditable;
use App\ChangeTracking\ChangeTracker;
use App\Models\Result;
use App\Models\SampleAnalysis;
use App\Support\DilutionCurve;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class UpdateAnalysisDilutions
{
    public function __construct(
        private EnsureEditable $ensureEditable,
        private CreateResultRow $createRow,
        private InvalidateAnalysisCalculation $invalidateCalculation,
        private RecalculateConfirmation $recalculateConfirmation,
        private ChangeTracker $changeTracker,
    ) {}

    /**
     * Replace the dilutions and replicates of one analysis, keeping the results already entered
     * for rows that remain. The profile or roaming settings are deliberately left untouched.
     *
     * @param  list<array{value: string|int|float, replicates?: int|null}>  $dilutions
     */
    public function handle(SampleAnalysis $analysis, array $dilutions): void
    {
        DB::transaction(function () use ($analysis, $dilutions): void {
            $sample = $this->ensureEditable->forSample($analysis->sample);

            $analysis = SampleAnalysis::query()
                ->with(['assayRecord.assayType.fields', 'confirmationRecord'])
                ->lockForUpdate()
                ->findOrFail($analysis->getKey());

            if (! $analysis->assayRecord?->dillution && ! $analysis->assayRecord?->replicates) {
                throw ValidationException::withMessages([
                    'analysis' => 'Voor deze analyse kunnen geen verdunningen of replica\'s worden gewijzigd.',
                ]);
            }

            $wanted = $this->wantedRows($dilutions);
            $existing = Result::query()->where('sa_id', $analysis->id)->orderBy('follow_no')->lockForUpdate()->get();
            $kept = $existing->reject(fn (Result $row): bool => ! isset($wanted[DilutionCurve::format((float) $row->df)])
                || (int) $row->rep > $wanted[DilutionCurve::format((float) $row->df)]);

            Result::query()->whereKey($existing->diff($kept)->modelKeys())->delete();

            // Numbering continues after the highest remaining plate so labels already printed stay unique.
            $followNumber = (int) $kept->max('follow_no');
            $data = $analysis->assayRecord->emptyResultData();

            foreach ($wanted as $dilution => $replicates) {
                for ($replicate = 0; $replicate <= $replicates; $replicate++) {
                    $present = $kept->contains(fn (Result $row): bool => DilutionCurve::format((float) $row->df) === (string) $dilution
                        && (int) $row->rep === $replicate);

                    if (! $present) {
                        $this->createRow->handle($analysis, (string) $dilution, $replicate, ++$followNumber, $data);
                    }
                }
            }

            $analysis->unsetRelation('results');

            if ($analysis->confirmationRecord !== null) {
                $this->recalculateConfirmation->handle($analysis, $analysis->confirmationRecord);
            }

            $this->changeTracker->changed(
                10,
                project: $sample->project,
                sample: $sample->id,
                said: $analysis->id,
                event: 'Verdunningen gewijzigd voor '.$sample->barcode,
                to: $this->asText($wanted),
            );
            $this->invalidateCalculation->handle($analysis);
        });
    }

    /**
     * @param  list<array{value: string|int|float, replicates?: int|null}>  $dilutions
     * @return array<string, int> replicates by dilution factor, highest concentration first
     */
    private function wantedRows(array $dilutions): array
    {
        $wanted = [];

        foreach ($dilutions as $dilution) {
            $key = DilutionCurve::format((float) $dilution['value']);
            $wanted[$key] = max($wanted[$key] ?? 0, max(0, (int) ($dilution['replicates'] ?? 0)));
        }

        if ($wanted === [] || ($problem = DilutionCurve::problem(array_keys($wanted))) !== null) {
            throw ValidationException::withMessages(['dilutions' => $problem ?? 'Kies minimaal één verdunning.']);
        }

        uksort($wanted, fn ($left, $right): int => (float) $right <=> (float) $left);

        return $wanted;
    }

    /**
     * The legacy text that was posted: one `-1=0.1` line per result row.
     *
     * @param  array<string, int>  $wanted
     */
    private function asText(array $wanted): string
    {
        $lines = [];

        foreach ($wanted as $dilution => $replicates) {
            array_push($lines, ...array_fill(0, $replicates + 1, DilutionCurve::line((float) $dilution)));
        }

        return implode("\n", $lines);
    }
}
