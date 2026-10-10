<?php

namespace App\Actions\Confirmations;

use App\Actions\AssuranceForms\UpdateAssuranceExpiryEvidence;
use App\Actions\AssuranceForms\UpdateConfirmationAssuranceValue;
use App\AssuranceForms\AssuranceValueRepository;
use App\ChangeTracking\ChangeTracker;
use App\Confirmations\AssayConfirmationConfiguration;
use App\Models\Confirmation;
use App\Models\ConfKeyStore;
use App\Models\Media;
use App\Models\SampleAnalysis;
use Illuminate\Validation\ValidationException;

class UpdateConfirmationMetadata
{
    public function __construct(
        private ConfirmationMutation $mutation,
        private RecalculateConfirmation $recalculate,
        private UpdateConfirmationAssuranceValue $assurance,
        private UpdateAssuranceExpiryEvidence $updateEvidence,
        private AssuranceValueRepository $assuranceValues,
        private ChangeTracker $changeTracker,
    ) {}

    public function handle(SampleAnalysis $analysis, string $df, int $rep, string $key, ?string $value): SampleAnalysis
    {
        if ($this->isAssuranceField($key)) {
            return $this->assurance->handle($analysis, $df, $rep, $key, $value ?? '');
        }

        return $this->mutation->execute($analysis, function (SampleAnalysis $analysis, ?Confirmation $confirmation) use ($df, $rep, $key, $value): SampleAnalysis {
            $confirmation = $this->mutation->requireConfirmation($confirmation);
            $this->mutation->assertScope($analysis, $df, $rep);

            [$mediaId, $field] = $this->parseKey($key);
            $steps = AssayConfirmationConfiguration::decode($analysis->assayRecord?->confirmation_script);
            $stepIndex = collect($steps)->search(fn (array $step): bool => (int) ($step['mediaId'] ?? 0) === $mediaId);

            if ($stepIndex === false) {
                throw ValidationException::withMessages(['key' => 'Dit bevestigingsveld hoort niet bij de analyse.']);
            }

            $metadata = is_array($confirmation->metadata) ? $confirmation->metadata : [];
            $previous = $metadata[$df][$rep][$stepIndex][$key] ?? false;
            $metadata[$df][$rep][$stepIndex][$key] = $value;
            $metadata[$df][$rep][$stepIndex][$key.'_user'] = auth()->id();

            if ($field === 'inzet' || $field === 'aflees') {
                $confirmation->metadata = $metadata;

                if ($field === 'inzet') {
                    $expiryDate = $this->assuranceValues->valueForAnalysisAndMedia($analysis, $mediaId);
                    $this->updateEvidence->handle($analysis, $mediaId, $value, $expiryDate);
                }
            } else {
                $innocdate = $metadata[$df][$rep][$stepIndex][$mediaId.'_inzet'] ?? null;

                if ($innocdate === null || $innocdate === '') {
                    throw ValidationException::withMessages(['value' => 'Vul eerst de inzetdatum in.']);
                }

                $control = ConfKeyStore::query()
                    ->where('innocdate', $innocdate)
                    ->where('media', $mediaId)
                    ->where('param', $field)
                    ->lockForUpdate()
                    ->first();
                $control ??= new ConfKeyStore([
                    'innocdate' => $innocdate,
                    'media' => $mediaId,
                    'param' => $field,
                ]);
                $control->value = $value;
                $control->save();
            }

            $this->recalculate->handle($analysis, $confirmation, true);
            $this->mutation->invalidate($analysis);

            $medium = Media::query()->find($mediaId);
            $isControl = ! in_array($field, ['inzet', 'aflees'], true);
            $this->changeTracker->changed($isControl ? 16 : 15,
                project: $analysis->project, sample: $analysis->sample, said: $analysis->id,
                event: 'Bevestiging aangepast: '.$analysis->sampleRecord?->barcode.', analyse: '.$analysis->assayRecord?->name
                    .', test:'.$medium?->short_name.' verdunning: '.$df.', replica: '.$rep
                    .($isControl ? ', controle:'.$field : ', veld:'.$key),
                from: $isControl ? false : $previous, to: $value);

            return $analysis;
        });
    }

    private function parseKey(string $key): array
    {
        preg_match('/^(\d+)_(inzet|aflees|poscontrol|negcontrol|blankcontrol)$/', $key, $matches);

        return [(int) $matches[1], $matches[2]];
    }

    private function isAssuranceField(string $key): bool
    {
        return preg_match('/^\d+_tht$/', $key) === 1;
    }
}
