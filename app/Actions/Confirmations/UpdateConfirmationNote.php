<?php

namespace App\Actions\Confirmations;

use App\Models\Confirmation;
use App\Models\SampleAnalysis;

class UpdateConfirmationNote
{
    public function __construct(private ConfirmationMutation $mutation) {}

    public function handle(SampleAnalysis $analysis, string $note): SampleAnalysis
    {
        return $this->mutation->execute($analysis, function (SampleAnalysis $analysis, ?Confirmation $confirmation) use ($note): SampleAnalysis {
            $confirmation = $this->mutation->requireConfirmation($confirmation);
            $confirmation->note = $note;
            $confirmation->save();

            return $analysis;
        });
    }
}
