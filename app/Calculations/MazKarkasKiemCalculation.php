<?php

namespace App\Calculations;

// TODO: To check for myself, not agentic.
// Where I didn't copy legacy behaviour exactly, because the legacy result depended on PHP version or was broken:
// Comparisons with >: I used the same reading as the existing Maz7218Calculation port (> may be followed by > or a number, never by 0). The legacy loose comparisons give different answers on PHP 7 and PHP 8.
// All counts above the maximum, with > on the most diluted plate: I use max_count as that plate's count. Legacy produced -INF or crashed here.
// Unexpected results: a dilution outside the legacy table, or no usable plates, gives "Fout resultaat gevonden" instead of -INF.
// Decision for you: if a confirmation ratio of 0 wipes out every colony, the result is still faithful to legacy and shows -inf. None of the five current assays use confirmation, so this can't happen today. Should I change it to "Fout resultaat gevonden" or an estimated minimum?

/**
 * Port of the legacy `mazKarkasKiem` script: the aerobic colony count of a carcass swab.
 */
class MazKarkasKiemCalculation extends MazKarkasCalculation
{
    protected function limitValues(bool $pig): array
    {
        return ['lower' => $pig ? 4.00 : 3.50, 'upper' => 5.00];
    }
}
