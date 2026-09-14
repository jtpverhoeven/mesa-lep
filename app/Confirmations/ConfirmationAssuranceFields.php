<?php

namespace App\Confirmations;

interface ConfirmationAssuranceFields
{
    public function fields(int $mediaId, array $media): array;
}
