<?php

namespace App\Confirmations;

use App\Models\SampleAnalysis;

interface ConfirmationAssuranceFields
{
    public function fields(SampleAnalysis $analysis, int $mediaId, array $media): array;
}
