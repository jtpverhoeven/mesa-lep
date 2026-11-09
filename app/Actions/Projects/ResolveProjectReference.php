<?php

namespace App\Actions\Projects;

use App\Models\Project;
use App\Support\Cvars;

class ResolveProjectReference
{
    public function __construct(private Cvars $cvars) {}

    public function handle(Project $project): ?string
    {
        $setting = $this->cvars->find('MESA_PROJECT_REFERENCE');
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
