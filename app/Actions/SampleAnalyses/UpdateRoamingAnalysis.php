<?php

namespace App\Actions\SampleAnalyses;

use App\Actions\Projects\EnsureEditable;
use App\Actions\Results\GetMetaAnalysisParent;
use App\Calculations\ResultCalculationResolver;
use App\Jobs\CalculateAnalysisResultJob;
use App\Keyrings\KeyringType;
use App\Models\Project;
use App\Models\ReferenceSource;
use App\Models\RoamingAnalysis;
use App\Models\Sample;
use App\Models\SampleAnalysis;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class UpdateRoamingAnalysis
{
    public function __construct(
        private ResultCalculationResolver $calculations,
        private GetMetaAnalysisParent $metaParent,
        private EnsureEditable $ensureEditable,
    ) {}

    public function handle(SampleAnalysis $analysis, array $settings): RoamingAnalysis
    {
        return DB::transaction(function () use ($analysis, $settings) {
            $sample = Sample::query()->lockForUpdate()->findOrFail($analysis->sample);
            $project = Project::query()->lockForUpdate()->findOrFail($sample->project);
            $this->ensureEditable->handle($project, KeyringType::Sample, $sample->id);

            $analysis = SampleAnalysis::query()
                ->with('assayRecord')
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

            if (array_key_exists('reference', $settings)) {
                $outputs = $this->calculations->referenceOutputs($analysis->assayRecord);
                $allowedKeys = array_merge(
                    array_map(fn (string $name): string => 'ref_'.$name, array_keys($outputs ?? [])),
                    array_keys($roaming->reference ?? []),
                );
                foreach ($settings['reference'] as $key => $value) {
                    if (! in_array($key, $allowedKeys, true)) {
                        throw ValidationException::withMessages(['reference' => 'Dit referentieveld hoort niet bij deze analyse.']);
                    }
                    if ($value !== null && ! is_scalar($value)) {
                        throw ValidationException::withMessages(['reference' => 'Een referentiewaarde moet tekst of een getal zijn.']);
                    }
                }
                $settings['reference'] = array_filter($settings['reference'], fn ($value): bool => $value !== null && trim((string) $value) !== '');
            }

            $references = $settings['reference'] ?? $roaming->reference ?? [];
            $sourceId = array_key_exists('reference_source', $settings) ? $settings['reference_source'] : $roaming->reference_source;
            if (collect($references)->contains(fn ($value): bool => $value !== null && trim((string) $value) !== '') && ! $sourceId) {
                throw ValidationException::withMessages([
                    'reference_source' => 'Een referentiebron is verplicht wanneer een referentiewaarde is ingevuld.',
                ]);
            }
            $this->validateReferenceSource($sourceId, $sample->client);

            $roaming->fill(collect($settings)->only([
                'dillutions',
                'replicates',
                'reference',
                'reference_scope',
                'reference_source',
            ])->all());
            $roaming->save();

            if ($roaming->wasChanged()) {
                $analysis->update(['is_ready' => false, 'storedResult' => null]);
                CalculateAnalysisResultJob::dispatch($analysis->id)->afterCommit();
                $parent = $this->metaParent->handle($analysis);
                if ($parent !== null) {
                    $parent->update(['is_ready' => false, 'storedResult' => null]);
                    CalculateAnalysisResultJob::dispatch($parent->id)->afterCommit();
                }
            }

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
