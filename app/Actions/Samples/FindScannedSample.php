<?php

namespace App\Actions\Samples;

use App\Models\Sample;

class FindScannedSample
{
    public function handle(string $scannedBarcode): ?Sample
    {
        $barcode = explode('.', $scannedBarcode)[0];
        $exact = Sample::query()->where('barcode', $barcode)->latest('id')->first();

        if ($exact !== null) {
            return $exact;
        }

        if ($this->isShorthand($barcode)) {
            preg_match('/^(L[ABC]?|R)?(\d+)$/i', $barcode, $parts);
            $prefix = strtoupper($parts[1]);
            $type = str_starts_with($prefix, 'L') ? 'L' : ($prefix === 'R' ? 'R' : 'S');
            $matrix = strlen($prefix) === 2 ? $prefix[1] : '-';

            return Sample::query()->where('follow_no', (int) $parts[2])->where('sample_type', $type)
                ->where('leg_type', $matrix)->latest('id')->first();
        }

        return Sample::query()->where('tht_code', $barcode)->latest('id')->first();
    }

    public function isShorthand(string $barcode): bool
    {
        return preg_match('/^(?:L[ABC]?|R)?\d{1,7}$/i', $barcode) === 1;
    }
}
