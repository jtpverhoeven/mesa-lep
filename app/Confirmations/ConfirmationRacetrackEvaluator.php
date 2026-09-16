<?php

namespace App\Confirmations;

use App\Models\SampleAnalysis;

class ConfirmationRacetrackEvaluator
{
    public function __construct(private ConfirmationAssuranceFields $assuranceFields) {}

    public function evaluate(
        SampleAnalysis $analysis,
        array $steps,
        array $contenders,
        array $metadata,
        array $media,
        array $controlValues = [],
    ): array {
        $steps = array_values($steps);
        $states = [];
        $reachedSteps = [];
        $tested = 0;
        $confirmed = 0;

        foreach (array_values($contenders) as $index => $contender) {
            $answers = [];
            $visibleSteps = [];
            $finished = false;
            $isConfirmed = false;

            foreach ($steps as $stepIndex => $step) {
                $visibleSteps[] = $stepIndex;
                $answer = $this->answer($contender, $stepIndex);
                $answers[(string) $stepIndex] = $answer;
                $reachedSteps[$stepIndex] = true;

                if ($this->emptyAnswer($answer)) {
                    break;
                }

                $disposition = (string) ($step['disposition'] ?? '');

                if ($disposition !== '?' && $answer !== $disposition) {
                    $finished = true;

                    for ($downstream = $stepIndex + 1; $downstream < count($steps); $downstream++) {
                        $answers[(string) $downstream] = null;
                    }

                    break;
                }

                if ($stepIndex === count($steps) - 1) {
                    $finished = true;
                    $isConfirmed = true;
                }
            }

            if ($finished) {
                $tested++;
            }

            if ($isConfirmed) {
                $confirmed++;
            }

            $states[] = [
                'index' => $index,
                'active' => ! $finished,
                'finished' => $finished,
                'confirmed' => $isConfirmed,
                'visible_steps' => $visibleSteps,
                'answers' => $answers,
            ];
        }

        $metadataFields = [];
        $inUse = [];

        foreach (array_keys($reachedSteps) as $stepIndex) {
            $step = $steps[$stepIndex];
            $mediaId = (int) ($step['mediaId'] ?? 0);
            $mediaInfo = $media[(string) $mediaId] ?? [];
            $stepMetadata = $metadata[(string) $stepIndex] ?? $metadata[$stepIndex] ?? [];
            $inUse[(string) $mediaId] = true;

            $metadataFields = [
                ...$metadataFields,
                ...$this->fieldsForStep($analysis, $mediaId, $stepMetadata, $mediaInfo, $controlValues),
            ];
        }

        $metadataComplete = collect($metadataFields)->every(function (array $field): bool {
            if (($field['required'] ?? false) && $this->emptyAnswer($field['value'] ?? null)) {
                return false;
            }

            return ! ($field['requires_explanation'] ?? false)
                || ! $this->emptyAnswer($field['explanation'] ?? null);
        });
        $scopeReady = $states !== []
            && collect($states)->every(fn (array $state): bool => $state['finished'])
            && $metadataComplete;

        return [
            'contenders' => $states,
            'in_use' => $inUse,
            'metadata_fields' => $metadataFields,
            'summary' => [
                'tested' => $tested,
                'confirmed' => $confirmed,
                'ratio' => $tested > 0 ? $confirmed / $tested : null,
                'scope_ready' => $scopeReady,
            ],
        ];
    }

    public function assuranceFields(SampleAnalysis $analysis, int $mediaId, array $media): array
    {
        return $this->assuranceFields->fields($analysis, $mediaId, $media);
    }

    private function answer(array $contender, int $stepIndex): mixed
    {
        return array_key_exists($stepIndex, $contender)
            ? $contender[$stepIndex]
            : ($contender[(string) $stepIndex] ?? '');
    }

    private function emptyAnswer(mixed $answer): bool
    {
        return $answer === null || $answer === '';
    }

    private function fieldsForStep(SampleAnalysis $analysis, int $mediaId, array $metadata, array $media, array $controlValues): array
    {
        $fields = [
            [
                'key' => $mediaId.'_inzet',
                'kind' => 'date',
                'available' => true,
                'required' => true,
                'value' => $metadata[$mediaId.'_inzet'] ?? null,
            ],
            [
                'key' => $mediaId.'_aflees',
                'kind' => 'date',
                'available' => true,
                'required' => true,
                'value' => $metadata[$mediaId.'_aflees'] ?? null,
            ],
        ];
        $controls = $this->jsonArray($media['confirmation_controls'] ?? null);
        $inoculationDate = (string) ($metadata[$mediaId.'_inzet'] ?? '');

        foreach (['pos' => 'poscontrol', 'neg' => 'negcontrol', 'blank' => 'blankcontrol'] as $flag => $param) {
            if (! $this->enabled($controls[$flag] ?? false)) {
                continue;
            }

            $fields[] = [
                'key' => $mediaId.'_'.$param,
                'kind' => 'control',
                'available' => true,
                'required' => true,
                'value' => $controlValues[$inoculationDate.'|'.$mediaId.'|'.$param] ?? null,
            ];
        }

        return [...$fields, ...$this->assuranceFields->fields($analysis, $mediaId, $media)];
    }

    private function jsonArray(mixed $value): array
    {
        if (is_array($value)) {
            return $value;
        }

        if (! is_string($value) || $value === '') {
            return [];
        }

        $decoded = json_decode($value, true);

        return is_array($decoded) ? $decoded : [];
    }

    private function enabled(mixed $value): bool
    {
        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }
}
