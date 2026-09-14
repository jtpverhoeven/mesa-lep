<?php

namespace App\Actions\Confirmations;

use App\Models\Confirmation;
use App\Models\SampleAnalysis;

class InitializeConfirmation
{
    public function __construct(private RecalculateConfirmation $recalculate) {}

    public function handle(SampleAnalysis $analysis, ?Confirmation $confirmation = null): Confirmation
    {
        $confirmation ??= new Confirmation(['said' => $analysis->id]);
        $this->recalculate->handle($analysis, $confirmation, true);

        return $confirmation->fresh();
    }
}
