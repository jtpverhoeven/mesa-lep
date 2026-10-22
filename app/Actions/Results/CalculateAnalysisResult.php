<?php

namespace App\Actions\Results;

use App\Actions\Confirmations\ApplyConfirmationWorkflowToResult;
use App\Actions\Confirmations\ResolveConfirmationDecision;
use App\Calculations\Contracts\HandlesIncompleteResults;
use App\Calculations\ResultCalculationCache;
use App\Calculations\ResultCalculationContext;
use App\Calculations\ResultCalculationResolver;
use App\Models\AssayProfile;
use App\Models\RoamingAnalysis;
use App\Models\SampleAnalysis;
use Illuminate\Support\Facades\DB;
use LogicException;

class CalculateAnalysisResult
{
    public function __construct(
        private CheckAnalysisResultsComplete $resultsComplete,
        private ResultCalculationResolver $calculations,
        private ResultCalculationCache $cache,
        private ResolveConfirmationDecision $resolveConfirmation,
        private ApplyConfirmationWorkflowToResult $applyConfirmation,
        private GetMetaAnalysisResults $metaResults,
        private GetMetaAnalysisParent $metaParent,
        private ApplyVetoResults $applyVetoes,
    ) {}

    public function handle(SampleAnalysis $analysis): array
    {
        return DB::transaction(function () use ($analysis) {
            $analysis = SampleAnalysis::query()
                ->with([
                    'assayRecord',
                    'assayProfile',
                    'projectRecord',
                    'results',
                    'confirmationRecord',
                    'roamingAnalysis',
                    'roamingSettings',
                    'vetoResults',
                ])
                ->lockForUpdate()
                ->findOrFail($analysis->getKey());

            if ($analysis->projectRecord?->auth_status && ! empty($analysis->storedResult)) {
                return $this->applyVetoes->handle($analysis, $analysis->storedResult);
            }

            $context = $this->calculationContext($analysis);

            $calculator = $this->calculations->resolve($context->assay);

            if (($analysis->storedResult['confirmation']['decision_required'] ?? false) === true
                && $this->cache->isCurrent($analysis->storedResult, $context, $calculator)) {
                return $this->applyVetoes->handle($analysis, $analysis->storedResult);
            }

            if (! $this->resultsComplete->handle($context, $calculator)) {
                $calculation = $calculator instanceof HandlesIncompleteResults
                    ? $calculator->incompleteResult($context)
                    : $this->incompleteResult();
                $calculation = $this->cache->stamp($calculation, $context, $calculator);
                $calculator = null;
            } else {
                $calculation = $this->cache->stamp(
                    $calculator->calculate($context),
                    $context,
                    $calculator,
                );
            }

            $workflow = $this->resolveConfirmation->handle($analysis, $context, $calculation);
            $calculation = $this->applyConfirmation->handle($context, $calculator, $calculation, $workflow);
            $calculation = $this->applyVetoes->handle($analysis, $calculation);

            $isReady = (bool) ($calculation['isReady'] ?? false);
            $calculation['isReady'] = $isReady;

            $analysis->is_ready = $isReady;
            $analysis->storedResult = $calculation;
            $analysis->save();

            return $calculation;
        });
    }

    private function calculationContext(SampleAnalysis $analysis): ResultCalculationContext
    {
        $assay = $analysis->assayRecord;
        $settings = $analysis->calculationSettings();

        if ($assay === null) {
            throw new LogicException("Sample analysis [{$analysis->id}] has no assay to calculate.");
        }

        if ($settings === null) {
            throw new LogicException("Sample analysis [{$analysis->id}] has no calculation settings.");
        }

        if ((int) $settings->assay !== (int) $assay->id) {
            throw new LogicException("Sample analysis [{$analysis->id}] has settings for a different assay.");
        }

        if ($settings instanceof AssayProfile && (int) $settings->research_profile !== (int) $analysis->profile) {
            throw new LogicException("Sample analysis [{$analysis->id}] has settings for a different profile.");
        }

        if ($settings instanceof RoamingAnalysis && (int) $settings->said !== (int) $analysis->id) {
            throw new LogicException("Sample analysis [{$analysis->id}] has roaming settings for a different analysis.");
        }

        if ((int) $assay->type === 4) {
            $analysis->loadMissing('sampleRecord');
        }

        return new ResultCalculationContext(
            $analysis,
            $assay,
            $settings,
            metaResults: (int) $assay->type === 4 ? $this->metaResults->handle($analysis) : [],
            metaParent: $this->metaParent->handle($analysis),
        );
    }

    private function incompleteResult(): array
    {
        return [
            'output' => ['result' => 'Niet afgerond'],
            'messageBag' => [],
            'reportIn' => 'result',
            'outputEn' => ['result' => 'Not completed'],
            'disposition' => [],
            'isReady' => false,
            'resultMask' => ['result' => 'result'],
            'resultHide' => [],
        ];
    }
}
