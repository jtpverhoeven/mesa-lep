<?php

use App\Calculations\DummyResultCalculation;
use App\Calculations\Maz7218Calculation;

return [
    'default' => DummyResultCalculation::class,

    'calculators' => [
        'maz7218' => Maz7218Calculation::class,
    ],
];
