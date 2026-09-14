<?php

namespace App\Actions\Confirmations;

use App\Confirmations\AssayConfirmationConfiguration;
use App\Models\Confirmation;
use App\Models\SampleAnalysis;
use Illuminate\Validation\ValidationException;

class SetConfirmationSupportMedium
{
    public function __construct(
        private ConfirmationMutation $mutation,
        private RecalculateConfirmation $recalculate,
    ) {}

    public function handle(SampleAnalysis $analysis, string $df, int $rep, int $mediaId, bool $active): SampleAnalysis
    {
        return $this->mutation->execute($analysis, function (SampleAnalysis $analysis, ?Confirmation $confirmation) use ($df, $rep, $mediaId, $active): SampleAnalysis {
            $confirmation = $this->mutation->requireConfirmation($confirmation);
            $this->mutation->assertScope($analysis, $df, $rep);
            $support = AssayConfirmationConfiguration::decode($analysis->assayRecord?->confirmation_support);

            if (! collect($support)->contains(fn (array $row): bool => (int) ($row['mediaId'] ?? 0) === $mediaId)) {
                throw ValidationException::withMessages(['media_id' => 'Dit medium is geen ondersteunend medium voor deze analyse.']);
            }

            $inUse = is_array($confirmation->in_use) ? $confirmation->in_use : [];
            $inUse[$df][$rep][$mediaId] = $active;
            $confirmation->in_use = $inUse;
            $this->recalculate->handle($analysis, $confirmation, true);
            $this->mutation->invalidate($analysis);

            return $analysis;
        });
    }
}
