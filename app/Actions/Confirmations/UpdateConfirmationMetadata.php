<?php

namespace App\Actions\Confirmations;

use App\Confirmations\AssayConfirmationConfiguration;
use App\Models\Confirmation;
use App\Models\ConfKeyStore;
use App\Models\SampleAnalysis;
use Illuminate\Validation\ValidationException;

class UpdateConfirmationMetadata
{
    public function __construct(
        private ConfirmationMutation $mutation,
        private RecalculateConfirmation $recalculate,
    ) {}

    public function handle(SampleAnalysis $analysis, string $df, int $rep, string $key, ?string $value): SampleAnalysis
    {
        return $this->mutation->execute($analysis, function (SampleAnalysis $analysis, ?Confirmation $confirmation) use ($df, $rep, $key, $value): SampleAnalysis {
            $confirmation = $this->mutation->requireConfirmation($confirmation);
            $this->mutation->assertScope($analysis, $df, $rep);
            if ($this->isAssuranceField($key)) {
                throw ValidationException::withMessages(['key' => 'Dit borgingsveld is nog niet beschikbaar.']);
            }

            [$mediaId, $field] = $this->parseKey($key);
            $steps = AssayConfirmationConfiguration::decode($analysis->assayRecord?->confirmation_script);
            $stepIndex = collect($steps)->search(fn (array $step): bool => (int) ($step['mediaId'] ?? 0) === $mediaId);

            if ($stepIndex === false) {
                throw ValidationException::withMessages(['key' => 'Dit bevestigingsveld hoort niet bij de analyse.']);
            }

            $metadata = is_array($confirmation->metadata) ? $confirmation->metadata : [];
            $metadata[$df][$rep][$stepIndex][$key] = $value;
            $metadata[$df][$rep][$stepIndex][$key.'_user'] = auth()->id();

            if ($field === 'inzet' || $field === 'aflees') {
                $confirmation->metadata = $metadata;
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
