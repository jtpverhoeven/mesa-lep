<?php

namespace App\Confirmations;

use App\Models\SampleAnalysis;

class PendingConfirmationAssuranceFields implements ConfirmationAssuranceFields
{
    public function fields(SampleAnalysis $analysis, int $mediaId, array $media): array
    {
        if ((int) ($media['hasDate'] ?? 0) !== 1) {
            return [];
        }

        return [[
            'key' => $mediaId.'_tht',
            'kind' => 'assurance_placeholder',
            'available' => false,
            'required' => false,
            'value' => null,
        ]];
    }
}
