<?php

namespace App\Actions\Reports;

use App\Models\Export;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use ZipArchive;

class DownloadReportZip
{
    public function __construct(private GetReportTransaction $transaction) {}

    /** @return array{path: string, filename: string} */
    public function handle(int $transaction): array
    {
        $exports = $this->transaction->handle($transaction);

        if ($exports->contains(fn (Export $export): bool => ! $export->pdfExists())) {
            throw ReportTransactionUnavailableException::missingPdf();
        }

        $first = $exports->first();
        $path = tempnam(sys_get_temp_dir(), 'reports-');
        $zip = new ZipArchive;

        if ($path === false || $zip->open($path, ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('The report archive could not be created.');
        }

        $namesInUse = [];

        foreach ($exports as $export) {
            $name = $export->reportName(true);
            $suffix = isset($namesInUse[$name]) ? '('.$namesInUse[$name]++.')' : '';
            $namesInUse[$name] ??= 1;
            $zip->addFromString($name.$suffix.'.pdf', Storage::disk('local')->get($export->storagePath()));
        }

        $zip->close();

        return [
            'path' => $path,
            'filename' => $exports->count().' rapportages uit MAZ-L '.$first->projectRecord?->reference.'.'.$first->revision.'.zip',
        ];
    }
}
