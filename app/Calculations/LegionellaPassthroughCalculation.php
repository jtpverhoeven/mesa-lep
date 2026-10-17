<?php

namespace App\Calculations;

use App\Actions\Results\CalculateAnalysisResult;
use App\Calculations\Contracts\HandlesIncompleteResults;
use App\Calculations\Contracts\ResultCalculation;

class LegionellaPassthroughCalculation implements HandlesIncompleteResults, ResultCalculation
{
    public function __construct(private CalculateAnalysisResult $calculateParent) {}

    public function calculate(ResultCalculationContext $context): array
    {
        $calculation = $this->incompleteResult($context);
        $parent = $context->metaParent;

        if ($parent === null) {
            $calculation['messageBag'] = ['Meta-analyse voor deze plaat is niet gevonden.'];

            return $calculation;
        }

        $parentResult = $this->calculateParent->handle($parent);
        $parent->refresh();
        $calculation['isReady'] = true;
        $calculation['calculationReady'] = true;
        $calculation['disposition'] = $parentResult['disposition'] ?? [];
        $calculation['meta_parent'] = (int) $parent->id;
        $calculation['metaParent'] = (int) $parent->id;
        $calculation['meta_said'] = [
            ...$parent->only(['id', 'sample', 'assay_base', 'conf_requested', 'is_ready']),
            'name' => $parent->assayRecord?->name,
        ];
        $calculation['confirmation'] = [
            ...($parentResult['confirmation'] ?? []),
            'target_analysis_id' => (int) $parent->id,
        ];
        $calculation['askForMetaParentConfirmation'] = (int) $parent->conf_requested === 0
            && ($parentResult['disposition']['kve'] ?? null) === '+'
            && ($parentResult['askForMetaConfirmation'] ?? false)
            && ($parentResult['confirmation']['decision_required'] ?? false);

        return $calculation;
    }

    public function allowsEmptyResultField(array $data, string $field): bool
    {
        return false;
    }

    public function incompleteResult(ResultCalculationContext $context): array
    {
        return [
            'output' => [],
            'outputEn' => [],
            'messageBag' => [],
            'reportIn' => 'kve',
            'reportable' => false,
            'disposition' => [],
            'isReady' => false,
            'calculationReady' => false,
            'resultMask' => ['kve' => 'dummy'],
            'resultHide' => [],
            'askForMetaParentConfirmation' => false,
            'confirmationTrigger' => [
                'eligible' => false,
                'numericValue' => null,
                'disposition' => null,
                'targetAnalysisId' => $context->metaParent?->id ?? (int) $context->analysis->id,
            ],
        ];
    }
}
