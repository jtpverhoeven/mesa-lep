<?php

namespace App\Actions\Printing;

use App\Support\Cvars;

class GetLabelPrintingSettings
{
    public function __construct(private Cvars $cvars) {}

    /**
     * @return array{enabled: bool, alignment: string}
     */
    public function handle(): array
    {
        $disabledName = config('printing.disabled_cvar', 'MESA_DISABLE_PRINTING');
        $alignmentName = config('printing.alignment_cvar', 'MESA_ZEBRA_ALIGN');

        return [
            'enabled' => $this->cvars->valueOrDefault($disabledName, '0') !== '1',
            'alignment' => (string) $this->cvars->valueOrDefault($alignmentName, ''),
        ];
    }
}
