<?php

namespace App\Actions\AuditTrail;

use App\Actions\Samples\FindScannedSample;
use App\Models\Project;

class GetAuditTrail
{
    public function __construct(
        private FindScannedSample $findSample,
        private BuildSampleAuditTrail $sampleTrail,
        private BuildProjectAuditTrail $projectTrail,
    ) {}

    /**
     * The audit trail of the sample behind a scanned barcode and of its project, or null for an unknown barcode.
     *
     * @return array{barcode: string, sample: array<string, mixed>, project: array<string, mixed>|null}|null
     */
    public function handle(string $scannedBarcode, bool $includeProject = true): ?array
    {
        $sample = $this->findSample->handle(trim($scannedBarcode));

        if ($sample === null) {
            return null;
        }

        $project = $includeProject ? Project::query()->find($sample->project) : null;

        return [
            'barcode' => $sample->barcode,
            'sample' => $this->sampleTrail->handle($sample),
            'project' => $project === null ? null : $this->projectTrail->handle($project),
        ];
    }
}
