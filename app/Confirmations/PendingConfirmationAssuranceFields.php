<?php

namespace App\Confirmations;

class PendingConfirmationAssuranceFields implements ConfirmationAssuranceFields
{
    public function fields(int $mediaId, array $media): array
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
