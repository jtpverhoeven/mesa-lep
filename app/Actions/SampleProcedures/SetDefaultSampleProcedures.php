<?php

namespace App\Actions\SampleProcedures;

use App\Support\Cvars;

class SetDefaultSampleProcedures
{
    public function __construct(private Cvars $cvars) {}

    public function handle(array $data): void
    {
        foreach ([
            'MESA_STD_SMPL_METHOD' => $data['default_proc'],
            'MESA_STD_LEGSMPL_METHOD' => $data['default_leg_proc'],
            'MESA_STD_RODACSMPL_METHOD' => $data['default_rodac_proc'],
        ] as $name => $value) {
            $this->cvars->set($name, (string) $value, ['default' => '0', 'description' => 'Standaard bemonsterprocedure']);
        }
    }
}
