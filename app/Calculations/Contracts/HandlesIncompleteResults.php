<?php

namespace App\Calculations\Contracts;

use App\Calculations\ResultCalculationContext;

interface HandlesIncompleteResults
{
    public function allowsEmptyResultField(array $data, string $field): bool;

    public function incompleteResult(ResultCalculationContext $context): array;
}
