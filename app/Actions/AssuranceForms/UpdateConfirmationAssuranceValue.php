<?php

namespace App\Actions\AssuranceForms;

use App\Actions\Confirmations\ConfirmationMutation;
use App\Confirmations\AssayConfirmationConfiguration;
use App\Models\SampleAnalysis;
use Illuminate\Validation\ValidationException;

class UpdateConfirmationAssuranceValue
{
    public function __construct(
        private ConfirmationMutation $mutation,
        private ResolveAssuranceDay $resolveDay,
        private GetOrCreateAssuranceForm $getOrCreate,
        private SynchronizeAssuranceForm $synchronize,
        private UpdateAssuranceFormField $updateField,
        private UpdateAssuranceExpiryEvidence $updateEvidence,
    ) {}

    public function handle(SampleAnalysis $analysis, string $df, int $rep, string $key, string $value): SampleAnalysis
    {
        return $this->mutation->execute($analysis, function (SampleAnalysis $analysis, $confirmation) use ($df, $rep, $key, $value): SampleAnalysis {
            $confirmation = $this->mutation->requireConfirmation($confirmation);
            $this->mutation->assertScope($analysis, $df, $rep);

            if (preg_match('/^(\d+)_tht$/', $key, $matches) !== 1) {
                throw ValidationException::withMessages(['key' => 'Dit borgingsveld is ongeldig.']);
            }

            $mediaId = (int) $matches[1];
            $steps = AssayConfirmationConfiguration::decode($analysis->assayRecord?->confirmation_script);
            $support = AssayConfirmationConfiguration::decode($analysis->assayRecord?->confirmation_support);
            $stepIndex = collect($steps)->search(fn (array $step): bool => (int) ($step['mediaId'] ?? 0) === $mediaId);
            $isSupport = collect($support)->contains(fn (array $row): bool => (int) ($row['mediaId'] ?? 0) === $mediaId);

            if ($stepIndex === false && ! $isSupport) {
                throw ValidationException::withMessages(['key' => 'Dit borgingsveld hoort niet bij de analyse.']);
            }

            if ($stepIndex !== false) {
                $track = is_array($confirmation->racetrack) ? $confirmation->racetrack : [];
                $scopeTrack = $track[$df][(string) $rep] ?? $track[$df][$rep] ?? [];
                $reached = collect($scopeTrack)->contains(
                    fn (mixed $contender): bool => is_array($contender) && array_key_exists($stepIndex, $contender),
                );

                if (! $reached) {
                    throw ValidationException::withMessages(['key' => 'Deze bevestigingsstap is nog niet bereikt.']);
                }
            }

            if ($isSupport) {
                $inUse = is_array($confirmation->in_use) ? $confirmation->in_use : [];
                $active = $inUse[$df][(string) $rep][$mediaId]
                    ?? $inUse[$df][$rep][$mediaId]
                    ?? false;

                if (! filter_var($active, FILTER_VALIDATE_BOOLEAN)) {
                    throw ValidationException::withMessages(['key' => 'Dit ondersteunende medium is niet actief.']);
                }
            }

            $analysis->loadMissing('sampleRecord');
            $sample = $analysis->sampleRecord;

            if ($sample === null || empty($sample->sample_innoculated)) {
                throw ValidationException::withMessages(['value' => 'Vul eerst de inzetdatum in.']);
            }

            $formDate = $this->resolveDay->handle((string) $sample->sample_innoculated)['form_date'];
            $form = $this->getOrCreate->handle($formDate);
            $fieldExists = array_key_exists((string) $mediaId, $form->decodedData()[3] ?? []);

            if (! $fieldExists) {
                $form = $this->synchronize->handle($form, [$mediaId]);
                $fieldExists = array_key_exists((string) $mediaId, $form->decodedData()[3] ?? []);
            }

            if (! $fieldExists) {
                throw ValidationException::withMessages(['key' => 'Dit medium staat niet op het borgingsformulier.']);
            }

            $this->updateField->handle($form, 'b3_'.$mediaId, $value);

            if ($stepIndex !== false) {
                $metadata = is_array($confirmation->metadata) ? $confirmation->metadata : [];
                $inoculationDate = $metadata[$df][(string) $rep][$stepIndex][$mediaId.'_inzet']
                    ?? $metadata[$df][$rep][$stepIndex][$mediaId.'_inzet']
                    ?? null;
                $this->updateEvidence->handle($analysis, $mediaId, $inoculationDate, $value);
            }

            $this->mutation->invalidate($analysis);

            return $analysis;
        });
    }
}
