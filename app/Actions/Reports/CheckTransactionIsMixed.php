<?php

namespace App\Actions\Reports;

use App\Actions\SampleFiles\ListProjectClientFiles;
use App\Models\Export;

class CheckTransactionIsMixed
{
    public function __construct(
        private GetReportTransaction $transaction,
        private ListProjectClientFiles $files,
        private ResolveExportSampleScope $scope,
    ) {}

    /**
     * A transaction is mixed when some of its reports receive selected files and others do not.
     *
     * @param  list<string>  $selectedHashes
     */
    public function handle(int $transaction, array $selectedHashes): bool
    {
        $exports = $this->transaction->handle($transaction);

        $receivesFiles = $exports->map(function (Export $export) use ($selectedHashes): bool {
            $sampleIds = $this->scope->handle($export);
            $reportFiles = $this->files->handle($export->projectRecord, $sampleIds === [] ? null : $sampleIds);

            return array_intersect($reportFiles->pluck('hash_name')->all(), $selectedHashes) !== [];
        });

        return $receivesFiles->unique()->count() > 1;
    }
}
