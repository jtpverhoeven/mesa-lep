<?php

namespace App\Actions\SampleAnalyses;

use App\Models\ReferenceSource;
use App\Models\RoamingAnalysis;
use App\Models\SampleAnalysis;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class UpdateRoamingAnalysis
{
    public function handle(SampleAnalysis $analysis, array $settings): RoamingAnalysis
    {
        return DB::transaction(function () use ($analysis, $settings) {
            $analysis = SampleAnalysis::query()
                ->with('sampleRecord')
                ->lockForUpdate()
                ->findOrFail($analysis->getKey());

            $roaming = RoamingAnalysis::query()
                ->where('said', $analysis->id)
                ->lockForUpdate()
                ->first();

            if (! $analysis->isRoaming() || $roaming === null) {
                throw ValidationException::withMessages([
                    'analysis' => 'Deze analyse is geen losse analyse.',
                ]);
            }

            if (array_key_exists('reference_source', $settings)) {
                $this->validateReferenceSource($settings['reference_source'], $analysis->sampleRecord->client);
            }

            $roaming->fill(collect($settings)->only([
                'dillutions',
                'replicates',
                'reference',
                'reference_scope',
                'reference_source',
            ])->all());
            $roaming->save();

            return $roaming->fresh('referenceSource');
        });
    }

    private function validateReferenceSource(?int $sourceId, int $clientId): void
    {
        if ($sourceId === null) {
            return;
        }

        $available = ReferenceSource::query()
            ->availableForClient($clientId)
            ->whereKey($sourceId)
            ->exists();

        if (! $available) {
            throw ValidationException::withMessages([
                'reference_source' => 'Deze referentiebron is niet beschikbaar voor de klant van dit monster.',
            ]);
        }
    }
}
