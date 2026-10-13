<?php

namespace App\Actions\Printing;

use App\Models\Cvar;

class GetLabelPrintingSettings
{
    /**
     * @return array{enabled: bool, alignment: string}
     */
    public function handle(): array
    {
        $disabledName = config('printing.disabled_cvar', 'MESA_DISABLE_PRINTING');
        $alignmentName = config('printing.alignment_cvar', 'MESA_ZEBRA_ALIGN');
        $settings = Cvar::query()->whereIn('cvar', [$disabledName, $alignmentName])->get()->keyBy('cvar');
        $disabled = $settings->get($disabledName);
        $alignment = $settings->get($alignmentName);

        return [
            'enabled' => (string) ($disabled?->value ?? $disabled?->default ?? '0') !== '1',
            'alignment' => (string) ($alignment?->value ?? $alignment?->default ?? ''),
        ];
    }
}
