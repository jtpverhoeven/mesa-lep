<?php

namespace App\Actions\Results;

use App\Models\SampleAnalysis;
use Illuminate\Database\Eloquent\Builder;

class GetMetaAnalysisParent
{
    public function handle(SampleAnalysis $analysis): ?SampleAnalysis
    {
        $analysis->loadMissing('assayRecord');

        if (preg_match(
            '/(?:^|\R)\h*file:\h*legionellapassthrough(?:\.class)?(?:\.php)?\h*(?=\R|$)/i',
            (string) $analysis->assayRecord?->script,
        ) !== 1) {
            return null;
        }

        return SampleAnalysis::query()
            ->with('assayRecord')
            ->where('sample', $analysis->sample)
            ->where('profile_group', $analysis->profile_group)
            ->whereKeyNot($analysis->id)
            ->whereHas('assayRecord', fn (Builder $assays): Builder => $assays->where('type', 4))
            ->orderBy('id')
            ->first();
    }
}
