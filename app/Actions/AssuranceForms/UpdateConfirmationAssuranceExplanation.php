<?php

namespace App\Actions\AssuranceForms;

use App\Actions\Confirmations\ConfirmationMutation;
use App\AssuranceForms\AssuranceValueRepository;
use App\Confirmations\AssayConfirmationConfiguration;
use App\Models\SampleAnalysis;
use Illuminate\Validation\ValidationException;

class UpdateConfirmationAssuranceExplanation
{
    public function __construct(
        private ConfirmationMutation $mutation,
        private AssuranceValueRepository $values,
        private UpdateAssuranceExplanation $updateExplanation,
    ) {}

    public function handle(SampleAnalysis $analysis, string $df, int $rep, string $key, string $explanation): SampleAnalysis
    {
        return $this->mutation->execute($analysis, function (SampleAnalysis $analysis, $confirmation) use ($df, $rep, $key, $explanation): SampleAnalysis {
            $confirmation = $this->mutation->requireConfirmation($confirmation);
            $this->mutation->assertScope($analysis, $df, $rep);
            preg_match('/^(\d+)_tht$/', $key, $matches);
            $mediaId = (int) ($matches[1] ?? 0);
            $steps = AssayConfirmationConfiguration::decode($analysis->assayRecord?->confirmation_script);
            $support = AssayConfirmationConfiguration::decode($analysis->assayRecord?->confirmation_support);

            if (! collect([...$steps, ...$support])->contains(fn (array $row): bool => (int) ($row['mediaId'] ?? 0) === $mediaId)) {
                throw ValidationException::withMessages(['key' => 'Dit borgingsveld hoort niet bij de analyse.']);
            }

            $analysis->loadMissing('sampleRecord');
            $form = $analysis->sampleRecord === null ? null : $this->values->formForSample($analysis->sampleRecord);

            if ($form === null) {
                throw ValidationException::withMessages(['explanation' => 'Het borgingsformulier bestaat nog niet.']);
            }

            $this->updateExplanation->handle($form, 'b3_'.$mediaId, $explanation);

            return $analysis;
        });
    }
}
