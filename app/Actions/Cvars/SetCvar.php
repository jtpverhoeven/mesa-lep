<?php

namespace App\Actions\Cvars;

use App\Models\Cvar;

class SetCvar
{
    public function handle(Cvar $cvar, string $value): Cvar
    {
        $cvar->update(['value' => $value]);

        return $cvar->refresh();
    }
}