<?php

use App\Calculations\DummyResultCalculation;
use App\Calculations\Maz7218Calculation;
use App\Calculations\RodacCalculation;

return [
    'default' => DummyResultCalculation::class,

    'calculators' => [
        'maz7218' => Maz7218Calculation::class,
        'rodac' => RodacCalculation::class,
    ],
];
