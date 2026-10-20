<?php

use App\Calculations\DummyResultCalculation;
use App\Calculations\LegionellaPassthroughCalculation;
use App\Calculations\Maz7218Calculation;
use App\Calculations\Maz7218GrensCalculation;
use App\Calculations\MazLegionella2019Calculation;
use App\Calculations\RodacCalculation;

return [
    'default' => DummyResultCalculation::class,

    'default_reference_outputs' => ['result' => 'Resultaat'],

    'reference_outputs' => [
        'maz7218' => ['kve' => 'kve'],
        'maz7218b' => ['kve' => 'kve'],
        'maz7218grens' => ['kve' => 'Aanwezigheid'],
        'maz7218notworking' => ['kve' => 'kve'],
        'mazvrij' => ['kve' => 'kve'],
        'mazlucht' => ['kve' => 'kve'],
        'mazschelp' => ['kve' => 'kve'],
        'mazkarkaskiem' => ['kve' => 'kve'],
        'mazkarkasentero' => ['kve' => 'kve'],
        'mazlegionella' => ['kve' => 'kve'],
        'mazlegionella2019' => ['kve' => 'kve'],
        'legionellapassthrough' => ['kve' => 'kve'],
        'legionella2019passthrough' => ['kve' => 'kve'],
        'rodac' => ['kve' => 'kve'],
        'ph' => ['kve' => 'pH waarde'],
        'mpn' => ['kve' => 'kve/100ml'],
    ],

    'calculators' => [
        'maz7218' => Maz7218Calculation::class,
        'maz7218grens' => Maz7218GrensCalculation::class,
        'mazLegionella2019' => MazLegionella2019Calculation::class,
        'legionellaPassthrough' => LegionellaPassthroughCalculation::class,
        'rodac' => RodacCalculation::class,
    ],
];
