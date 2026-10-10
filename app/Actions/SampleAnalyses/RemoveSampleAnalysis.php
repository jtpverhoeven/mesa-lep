<?php

namespace App\Actions\SampleAnalyses;

use App\ChangeTracking\ChangeTracker;
use App\Models\Result;
use App\Models\RoamingAnalysis;
use App\Models\Sample;
use App\Models\SampleAnalysis;
use Illuminate\Support\Facades\DB;

class RemoveSampleAnalysis
{
    public function __construct(private RefreshSampleIsEmpty $refreshSampleIsEmpty, private ChangeTracker $changeTracker) {}

    public function handle(SampleAnalysis $analysis): void
    {
        DB::transaction(function () use ($analysis) {
            $analysis = SampleAnalysis::query()->lockForUpdate()->findOrFail($analysis->getKey());
            $sample = Sample::query()->lockForUpdate()->findOrFail($analysis->sample);

            $this->changeTracker->changed(7, project: $analysis->project, sample: $sample->id, said: $analysis->id,
                event: 'Analyse '.$analysis->assayRecord?->name.' verwijderd van monster '.$sample->barcode, from: false, to: false);

            Result::query()->where('sa_id', $analysis->id)->delete();
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

            $this->refreshSampleIsEmpty->handle($sample);
        });
    }
}
