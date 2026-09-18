<?php

namespace App\Actions\Results;

use App\Actions\Confirmations\GetConfirmation;
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
        private GetConfirmation $confirmation,
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
                'confirmationRecord',
            ])
            ->findOrFail($analysis->getKey());
        $results = app(CreateAnalysisResults::class)->handle($analysis);
        $calculationState = $this->calculationState($analysis);
        $reportIn = $calculationState['calculation']['reportIn'] ?? null;
        $references = $analysis->calculationSettings()?->reference ?? [];
        $assayFields = json_decode($analysis->assayRecord?->custom_fields ?: '{}', true);

        if ($calculationState['queued']) {
            CalculateAnalysisResultJob::dispatch($analysis->id)->afterCommit();
        }

        return [
            'analysis' => $analysis->only([
                'id', 'sample', 'profile', 'assay', 'assay_base', 'roaming_id', 'conf_requested', 'is_ready',
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
            'unit' => is_array($assayFields) ? ($assayFields['resultin'] ?? null) : null,
            'reference' => $reportIn === null ? null : ($references['ref_'.$reportIn] ?? null),
            'confirmation' => $this->confirmation->handle($analysis),
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
