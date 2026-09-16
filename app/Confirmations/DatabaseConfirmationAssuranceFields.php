<?php

namespace App\Confirmations;

use App\AssuranceForms\AssuranceValueAssessment;
use App\AssuranceForms\AssuranceValueRepository;
use App\Models\SampleAnalysis;
use Carbon\CarbonImmutable;

class DatabaseConfirmationAssuranceFields implements ConfirmationAssuranceFields
{
    public function __construct(
        private AssuranceValueRepository $values,
        private AssuranceValueAssessment $assessment,
    ) {}

    public function fields(SampleAnalysis $analysis, int $mediaId, array $media): array
    {
        if ((int) ($media['hasDate'] ?? 0) !== 1) {
            return [];
        }

        $analysis->loadMissing('sampleRecord');
        $sample = $analysis->sampleRecord;
        $form = $sample === null ? null : $this->values->formForSample($sample);
        $info = $this->values->warningForAnalysisAndMedia($analysis, $mediaId);
        $value = $info['value'];
        $available = $sample !== null && ! empty($sample->sample_innoculated);
        $outOfSpecification = $available && $form !== null
            && $this->assessment->isOutOfSpecification($value, CarbonImmutable::createFromTimestamp((int) $form->date, 'Europe/Amsterdam')->format('Y-m-d'), $media);
        $hasEvidence = (bool) $info['outOfDateHere'];

        return [[
            'key' => $mediaId.'_tht',
            'kind' => (int) ($media['type'] ?? 0) === 3 ? 'material' : 'date',
            'available' => $available,
            'required' => $available,
            'value' => $value,
            'acceptable_range' => $media['acceptable_range'] ?? null,
            'out_of_specification' => $outOfSpecification,
            'out_of_date_here' => $hasEvidence,
            'out_of_date_text' => $info['sampleText'],
            'explanation' => $info['explanation'],
            'requires_explanation' => ($outOfSpecification || $hasEvidence)
                && ! $this->assessment->isNotApplicable($value),
        ]];
    }
}
