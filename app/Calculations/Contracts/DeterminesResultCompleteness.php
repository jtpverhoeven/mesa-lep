<?php

namespace App\Calculations\Contracts;

use App\Calculations\ResultCalculationContext;

interface DeterminesResultCompleteness
{
    public function resultsAreComplete(ResultCalculationContext $context): bool;
}
