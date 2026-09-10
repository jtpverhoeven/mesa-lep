<?php

namespace App\Actions\SampleAnalyses;

use App\Models\Sample;
use App\Models\SampleAnalysis;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ReorderSampleAnalyses
{
    public function handle(Sample $sample, array $analysisIds): void
    {
        DB::transaction(function () use ($sample, $analysisIds) {
            $sample = Sample::query()->lockForUpdate()->findOrFail($sample->getKey());
            $submittedIds = collect($analysisIds)->map(fn ($id) => (int) $id)->values();
            $analyses = SampleAnalysis::query()
                ->where('sample', $sample->id)
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            if ($submittedIds->duplicates()->isNotEmpty()
                || $submittedIds->sort()->values()->all() !== $analyses->keys()->sort()->values()->all()) {
                throw ValidationException::withMessages([
                    'analyses' => 'De volgorde moet alle analyses van dit monster precies eenmaal bevatten.',
                ]);
            }

            foreach ($analysisIds as $index => $analysisId) {
                $analyses->get((int) $analysisId)->update(['project_order' => $index + 1]);
            }
        });
    }
}
