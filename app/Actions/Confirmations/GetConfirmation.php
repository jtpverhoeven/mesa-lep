<?php

namespace App\Actions\Confirmations;

use App\Confirmations\ConfirmationState;
use App\Models\Confirmation;
use App\Models\SampleAnalysis;

class GetConfirmation
{
    public function __construct(private ConfirmationState $state) {}

    public function handle(SampleAnalysis $analysis, ?string $df = null, int $rep = 0): array
    {
        $analysis = SampleAnalysis::query()
            ->with(['assayRecord', 'projectRecord', 'results'])
            ->findOrFail($analysis->getKey());
        $confirmation = Confirmation::query()->where('said', $analysis->id)->first();

        return $this->state->build($analysis, $confirmation, $df, $rep);
    }
}
