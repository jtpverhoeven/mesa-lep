<?php

namespace App\Actions\Reports;

use App\Models\Export;
use UnexpectedValueException;

class ResolveExportSampleScope
{
    /** @return list<int> */
    public function handle(Export $export): array
    {
        $rawScope = $export->getRawOriginal('sample_id_scope');

        if ($rawScope === null) {
            return $export->sample_id === null ? [] : [(int) $export->sample_id];
        }

        $scope = is_array($rawScope) ? $rawScope : json_decode($rawScope, true);

        if (! is_array($scope) || ! array_is_list($scope)) {
            throw new UnexpectedValueException("Export [{$export->id}] has an invalid sample ID scope.");
        }

        $sampleIds = [];

        foreach ($scope as $sampleId) {
            $validatedId = filter_var($sampleId, FILTER_VALIDATE_INT);

            if ($validatedId === false || $validatedId < 1) {
                throw new UnexpectedValueException("Export [{$export->id}] has an invalid sample ID scope.");
            }

            $sampleIds[] = $validatedId;
        }

        return $sampleIds;
    }
}
