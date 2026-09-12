<?php

namespace App\Actions\Results;

use App\Calculations\ResultCalculationContext;

class CheckAnalysisResultsComplete
{
    public function handle(ResultCalculationContext $context): bool
    {
        $expectedRows = $this->expectedRows($context);

        if ($expectedRows === []) {
            return false;
        }

        foreach ($context->analysis->results as $result) {
            $row = $this->rowKey((float) $result->df, (int) $result->rep);

            if (! isset($expectedRows[$row])) {
                return false;
            }

            unset($expectedRows[$row]);

            if (! is_array($result->data)) {
                return false;
            }

            foreach ($result->data as $value) {
                if ($value === '' || $value === null) {
                    return false;
                }
            }
        }

        return $expectedRows === [];
    }

    /**
     * @return array<string, true>
     */
    private function expectedRows(ResultCalculationContext $context): array
    {
        $dilutions = array_values($context->settings->dillutions ?? []);

        if ($dilutions === []) {
            if ((int) $context->assay->type === 4) {
                return [];
            }

            $dilutions = [1];
        }

        $replicates = max(0, (int) $context->settings->replicates);
        $rows = [];

        foreach ($dilutions as $dilution) {
            if (! is_numeric($dilution) || (float) $dilution <= 0) {
                return [];
            }

            for ($replicate = 0; $replicate <= $replicates; $replicate++) {
                $row = $this->rowKey((float) $dilution, $replicate);

                if (isset($rows[$row])) {
                    return [];
                }

                $rows[$row] = true;
            }
        }

        return $rows;
    }

    private function rowKey(float $dilution, int $replicate): string
    {
        $dilution = rtrim(rtrim(sprintf('%.10F', $dilution), '0'), '.');

        return "{$dilution}:{$replicate}";
    }
}
