<?php

namespace App\Calculations\Contracts;

use App\Calculations\ResultCalculationContext;

interface ResultCalculation
{
    public function calculate(ResultCalculationContext $context): array;
}
