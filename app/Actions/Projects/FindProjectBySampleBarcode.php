<?php

namespace App\Actions\Projects;

use App\Models\Sample;

/**
 * Port of legacy samplesController::fetchSampleProject as used by the project barcode scans.
 *
 * Unlike FindScannedSample, shorthands of five characters or fewer only resolve legionella samples
 * ("L" + follow number) or normal "S" samples (bare follow number). Rodac samples share the regular
 * follow numbers but can therefore only be found by their full barcode or THT code.
 */
class FindProjectBySampleBarcode
{
    public function handle(string $scannedBarcode): ?int
    {
        $barcode = explode('.', $scannedBarcode)[0];

        if ($barcode === '') {
            return null;
        }

        if (strlen($barcode) <= 5) {
            $isLegionella = str_starts_with($barcode, 'L');
            $followNumber = $isLegionella ? substr($barcode, 1) : $barcode;

            if (! ctype_digit($followNumber)) {
                return null;
            }

            $project = Sample::query()
                ->where('sample_type', $isLegionella ? 'L' : 'S')
                ->where('follow_no', (int) $followNumber)
                ->orderByDesc('id')
                ->value('project');
        } else {
            $project = Sample::query()
                ->where(fn ($samples) => $samples->where('barcode', $barcode)->orWhere('tht_code', $barcode))
                ->orderBy('id')
                ->value('project');
        }

        return $project !== null ? (int) $project : null;
    }
}
