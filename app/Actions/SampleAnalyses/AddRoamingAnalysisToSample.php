<?php

namespace App\Actions\SampleAnalyses;

use App\Models\Assay;
use App\Models\ReferenceSource;
use App\Models\RoamingAnalysis;
use App\Models\Sample;
use App\Models\SampleAnalysis;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AddRoamingAnalysisToSample
{
    public function handle(Sample $sample, Assay $assay, array $settings): SampleAnalysis
    {
        return DB::transaction(function () use ($sample, $assay, $settings) {
            $sample = Sample::query()->findOrFail($sample->getKey());
            $assay = Assay::query()->whereKey($assay->getKey())->where('active', 1)->first();

            if ($assay === null) {
                throw ValidationException::withMessages([
                    'assay' => 'Deze analyse is niet actief.',
                ]);
            }

            $this->validateReferenceSource($settings['reference_source'] ?? null, $sample);

            $analysis = app(CreateSampleAnalyses::class)->handle($sample, [[
                'profile' => 0,
                'assay' => $assay->id,
                'assay_base' => $assay->id,
                'original_assay_base' => $assay->original_id,
            ]])->first();

            $roaming = RoamingAnalysis::create([
                'said' => $analysis->id,
                'assay' => $assay->id,
                'dillutions' => $settings['dillutions'] ?? [],
                'replicates' => $settings['replicates'] ?? 0,
                'reference' => $settings['reference'] ?? [],
                'reference_scope' => $settings['reference_scope'] ?? '',
                'reference_source' => $settings['reference_source'] ?? null,
            ]);

            $analysis->roamingAnalysis()->associate($roaming);
            $analysis->save();

            return $analysis->load(['assayRecord', 'roamingAnalysis.referenceSource']);
        });
    }

    private function validateReferenceSource(?int $sourceId, Sample $sample): void
    {
        if ($sourceId === null) {
            return;
        }

        $available = ReferenceSource::query()
            ->availableForClient($sample->client)
            ->whereKey($sourceId)
            ->exists();

        if (! $available) {
            throw ValidationException::withMessages([
                'reference_source' => 'Deze referentiebron is niet beschikbaar voor de klant van dit monster.',
            ]);
        }
    }
}
