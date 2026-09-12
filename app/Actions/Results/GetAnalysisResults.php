<?php

namespace App\Actions\Results;

use App\Calculations\Exceptions\ResultCalculationException;
use App\Calculations\ResultCalculationCache;
use App\Calculations\ResultCalculationContext;
use App\Calculations\ResultCalculationResolver;
use App\Jobs\CalculateAnalysisResultJob;
use App\Models\SampleAnalysis;

class GetAnalysisResults
{
    public function __construct(
        private ResultCalculationResolver $calculations,
        private ResultCalculationCache $cache,
    ) {}

    public function handle(SampleAnalysis $analysis): array
    {
        $analysis = SampleAnalysis::query()
            ->with([
                'assayProfile',
                'assayRecord.assayType.fields',
                'projectRecord',
                'roamingAnalysis',
                'roamingSettings',
            ])
            ->findOrFail($analysis->getKey());
        $results = app(CreateAnalysisResults::class)->handle($analysis);
        $calculationState = $this->calculationState($analysis);

        if ($calculationState['queued']) {
            CalculateAnalysisResultJob::dispatch($analysis->id)->afterCommit();
        }

        return [
            'analysis' => $analysis->only([
                'id', 'sample', 'profile', 'assay', 'assay_base', 'roaming_id',
            ]),
            'fields' => $analysis->assayRecord?->assayType?->fields
                ->map(fn ($field) => [
                    'name' => $field->name,
                    'label' => $field->alias ?: $field->name,
                    'type' => $field->type,
                    'position' => $field->pos,
                    'filter' => $field->filter,
                ])->values()->all() ?? [],
            'rows' => $results->map(fn ($result) => $result->only([
                'id', 'sample', 'sa_id', 'follow_no', 'profile', 'assay', 'assay_base',
                'roaming_id', 'df', 'rep', 'data',
            ]))->values()->all(),
            'calculation' => $calculationState['calculation'],
            'calculation_queued' => $calculationState['queued'],
            'calculation_unavailable' => $calculationState['unavailable'],
        ];
    }

    /**
     * @return array{calculation: mixed, queued: bool, unavailable: string|null}
     */
    private function calculationState(SampleAnalysis $analysis): array
    {
        if ($analysis->projectRecord?->auth_status && ! empty($analysis->storedResult)) {
            return [
                'calculation' => $analysis->storedResult,
                'queued' => false,
                'unavailable' => null,
            ];
        }

        $assay = $analysis->assayRecord;
        $settings = $analysis->calculationSettings();

        if ($assay === null || $settings === null) {
            return [
                'calculation' => null,
                'queued' => false,
                'unavailable' => 'De instellingen voor deze berekening zijn niet beschikbaar.',
            ];
        }

        try {
            $context = new ResultCalculationContext($analysis, $assay, $settings);
            $calculator = $this->calculations->resolve($assay);
        } catch (ResultCalculationException $exception) {
            return [
                'calculation' => null,
                'queued' => false,
                'unavailable' => $exception->userMessage(),
            ];
        }

        $current = $this->cache->isCurrent($analysis->storedResult, $context, $calculator);

        return [
            'calculation' => $current ? $analysis->storedResult : null,
            'queued' => ! $current,
            'unavailable' => null,
        ];
    }
}
