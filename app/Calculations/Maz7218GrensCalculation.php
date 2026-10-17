<?php

namespace App\Calculations;

use App\Calculations\Contracts\HandlesIncompleteResults;
use App\Calculations\Contracts\ResultCalculation;
use App\Models\Result;

class Maz7218GrensCalculation implements HandlesIncompleteResults, ResultCalculation
{
    private const POSITIVE_VALUES = ['+', '1', '>', 'J', 'Y'];

    private const NEGATIVE_VALUES = ['-', '0', 'N'];

    public function calculate(ResultCalculationContext $context): array
    {
        $variables = $this->externalVariables((string) $context->assay->script);
        $calculation = $this->incompleteResult($context);
        $reportLabel = $calculation['resultMask']['kve'];

        $results = $context->analysis->results
            ->filter(fn (Result $result): bool => (int) $result->rep === 0)
            ->sortByDesc(fn (Result $result): float => (float) $result->df);

        if ($results->isEmpty()) {
            return $calculation;
        }

        foreach ($results as $result) {
            if (! is_array($result->data) || ! array_key_exists('kve', $result->data)) {
                return $calculation;
            }

            foreach ($result->data as $field => $value) {
                if (($value === '' || $value === null) && ! $this->allowsEmptyResultField($result->data, $field)) {
                    return $calculation;
                }
            }
        }

        $value = (string) $results->first()->data['kve'];
        $positive = in_array($value, self::POSITIVE_VALUES);
        $usesConfirmation = (int) $context->assay->confirmation === 1;
        $confirmationDeclined = (int) $context->analysis->conf_requested === 2;
        $withConfirmation = $usesConfirmation && ! $confirmationDeclined;
        $negative = in_array($value, self::NEGATIVE_VALUES) || ($withConfirmation && $value === '<');
        $border = null;
        $disposition = null;

        if (! $context->assay->replicates || $confirmationDeclined) {
            $border = $positive ? 1 : ($negative ? 0 : 3);
            $disposition = $negative ? '-' : (($positive || ! $withConfirmation) ? '+' : null);

            if ($positive && $withConfirmation) {
                $ratio = $this->confirmationRatio($context);
                $border = $ratio === null
                    ? ((int) $context->assay->confirmation_type === 1 ? 0 : 3)
                    : ($ratio > 0 ? 1 : 0);
                $disposition = $border === 0 ? '-' : '+';
            }
        }

        $calculation['calculationReady'] = true;
        $calculation['confirmationTrigger'] = [
            'eligible' => $positive,
            'numericValue' => $positive ? 1 : null,
            'disposition' => $positive ? '+' : ($negative ? '-' : null),
            'targetAnalysisId' => (int) $context->analysis->id,
        ];
        $calculation['isReady'] = ! $withConfirmation || $negative || ($context->confirmation?->isComplete() && $border !== 3);

        if ($disposition !== null) {
            $calculation['disposition']['kve'] = $disposition;
        }

        $authorised = $context->analysis->relationLoaded('projectRecord')
            && (bool) $context->analysis->projectRecord?->auth_status;

        if (! $authorised && $disposition === '+' && isset($variables['toonMeldingIndienPositief'])) {
            $calculation['messageBag'][] = $variables['toonMeldingIndienPositief'];
        }

        $calculation['confirmationPendingResult'] = $border === 0 ? [] : [
            'output' => ['kve' => 'Niet afgerond'],
            'outputEn' => ['kve' => 'Not completed'],
            'disposition' => ['kve' => ''],
        ];

        if ($border !== 0 && (int) $context->analysis->conf_requested === 1 && ! $context->confirmation?->isComplete()) {
            return array_replace($calculation, $calculation['confirmationPendingResult']);
        }

        $calculation['output'] = ['kve' => ''];
        $calculation['outputEn'] = ['kve' => ''];

        if ($border === 3) {
            $calculation['output']['limsResultaat'] = 'Niet afgerond';

            return $calculation;
        }

        $replace = $this->booleanVariable($variables, $border === 1 ? 'bijPositiefResultaatVervangen' : 'bijNegatiefResultaatVervangen');

        if ($replace) {
            $replacement = json_decode($variables['resultaatVervangenMet'] ?? '', true);
            $calculation['output']['kve'] = $replacement['nl'] ?? '';
            $calculation['outputEn']['kve'] = $replacement['en'] ?? '';

            if (isset($variables['nieuwResultaatDispositie'])) {
                $calculation['disposition']['kve'] = $variables['nieuwResultaatDispositie'];
            }

            return $calculation;
        }

        if ($border !== 1) {
            $calculation['output']['kve'] = 'Niet aangetoond';
            $calculation['outputEn']['kve'] = 'Not detected';

            return $calculation;
        }

        if ($this->booleanVariable($variables, 'analyseIsPCR')) {
            if ($this->booleanVariable($variables, 'bijPositiefVerdachtWeergevenOpRapport')) {
                $calculation['output']['kve'] = 'Verdacht';
                $calculation['outputEn']['kve'] = 'Suspected';
            } elseif ($this->booleanVariable($variables, 'bijPositiefNietOpRapport')) {
                $calculation['output'][$reportLabel.' '] = 'Verdacht';
            } else {
                $calculation['output']['kve'] = 'Aangetoond';
                $calculation['outputEn']['kve'] = 'Detected';
            }

            return $calculation;
        }

        $addition = $usesConfirmation && $confirmationDeclined ? ' <sup>**</sup>' : '';
        $calculation['output']['kve'] = 'Aangetoond'.$addition;
        $calculation['outputEn']['kve'] = 'Detected'.$addition;

        return $calculation;
    }

    public function allowsEmptyResultField(array $data, string $field): bool
    {
        return $field === 'Ctwaarde' && in_array((string) ($data['kve'] ?? ''), ['-', '0']);
    }

    public function incompleteResult(ResultCalculationContext $context): array
    {
        $customFields = json_decode($context->assay->custom_fields ?? '{}', true);

        return [
            'output' => ['kve' => 'Niet afgerond'],
            'outputEn' => ['kve' => 'Not completed'],
            'messageBag' => [],
            'reportIn' => 'kve',
            'disposition' => [],
            'isReady' => false,
            'calculationReady' => false,
            'resultMask' => ['kve' => ($customFields['resultin'] ?? '') ?: 'kve'],
            'resultHide' => [],
            'confirmationTrigger' => [
                'eligible' => false,
                'numericValue' => null,
                'disposition' => null,
                'targetAnalysisId' => (int) $context->analysis->id,
            ],
        ];
    }

    /** @return array<string, string> */
    private function externalVariables(string $script): array
    {
        preg_match_all('/\$(.*)/', $script, $matches);
        $variables = [];

        foreach ($matches[1] as $declaration) {
            $parts = explode('=', $declaration);

            if (count($parts) >= 2) {
                $variables[$parts[0]] = trim($parts[1]);
            }
        }

        return $variables;
    }

    /** @param array<string, string> $variables */
    private function booleanVariable(array $variables, string $name): bool
    {
        return filter_var($variables[$name] ?? false, FILTER_VALIDATE_BOOLEAN);
    }

    private function confirmationRatio(ResultCalculationContext $context): ?float
    {
        $scope = (int) $context->assay->confirmation_type === 0 ? 'global' : 1;

        if ($context->confirmation !== null) {
            return $context->confirmation->ratios[$scope][0] ?? null;
        }

        if (! $context->analysis->relationLoaded('confirmationRecord')) {
            return null;
        }

        $ratio = $context->analysis->confirmationRecord?->metadata[$scope][0]['ratio'] ?? null;

        return is_numeric($ratio) ? (float) $ratio : null;
    }
}
