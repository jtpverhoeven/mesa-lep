<?php

namespace App\Actions\Projects;

use App\Models\Cvar;
use App\Models\Project;

class ResolveProjectReference
{
    public function handle(Project $project): ?string
    {
        $setting = Cvar::query()->where('cvar', 'MESA_PROJECT_REFERENCE')->first();
        $mode = (string) (blank($setting?->value) ? $setting?->default : $setting->value);
        $firstSample = $project->samples()->oldest('id')->first(['id', 'barcode']);
        $reference = match ($mode) {
            '1' => $project->id,
            '2' => $firstSample?->id,
            '3' => $firstSample?->barcode,
            default => null,
        };

        return $reference === null ? null : (string) $reference;
    }
}
