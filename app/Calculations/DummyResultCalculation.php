<?php

namespace App\Calculations;

use App\Calculations\Contracts\ResultCalculation;

class DummyResultCalculation implements ResultCalculation
{
    public function calculate(ResultCalculationContext $context): array
    {
        return [
            'output' => [
                'result' => 'calculation succesfull '.random_int(1000, 9999),
            ],
            'messageBag' => [],
            'reportIn' => 'result',
            'outputEn' => [],
            'disposition' => [],
            'isReady' => true,
            'resultMask' => ['result' => 'result'],
            'resultHide' => [],
        ];
    }
}
