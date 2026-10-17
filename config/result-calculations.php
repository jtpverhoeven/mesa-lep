<?php

use App\Calculations\DummyResultCalculation;
use App\Calculations\LegionellaPassthroughCalculation;
use App\Calculations\Maz7218Calculation;
use App\Calculations\Maz7218GrensCalculation;
use App\Calculations\MazLegionella2019Calculation;
use App\Calculations\RodacCalculation;

return [
    'default' => DummyResultCalculation::class,

    'calculators' => [
        'maz7218' => Maz7218Calculation::class,
        'maz7218grens' => Maz7218GrensCalculation::class,
        'mazLegionella2019' => MazLegionella2019Calculation::class,
        'legionellaPassthrough' => LegionellaPassthroughCalculation::class,
        'rodac' => RodacCalculation::class,
    ],
];
