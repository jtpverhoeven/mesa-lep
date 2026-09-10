<?php

namespace App\Actions\SampleAnalyses;

use App\Models\RoamingAnalysis;
use App\Models\Sample;
use App\Models\SampleAnalysis;
use Illuminate\Support\Facades\DB;

class RemoveSampleAnalysis
{
    public function handle(SampleAnalysis $analysis): void
    {
        DB::transaction(function () use ($analysis) {
            $analysis = SampleAnalysis::query()->lockForUpdate()->findOrFail($analysis->getKey());
            $sample = Sample::query()->lockForUpdate()->findOrFail($analysis->sample);

            RoamingAnalysis::query()->where('said', $analysis->id)->delete();
            $analysis->delete();

            SampleAnalysis::query()
                ->where('sample', $sample->id)
                ->orderBy('project_order')
                ->orderBy('follow_number')
                ->get()
                ->each(fn (SampleAnalysis $remaining, int $index) => $remaining->update([
                    'project_order' => $index + 1,
                ]));

            $sample->update([
                'isEmpty' => SampleAnalysis::query()->where('sample', $sample->id)->doesntExist() ? 1 : 0,
            ]);
        });
    }
}
