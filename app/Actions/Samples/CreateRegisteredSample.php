<?php

namespace App\Actions\Samples;

use App\Actions\ProductGroups\FindClientDefaultProductGroup;
use App\Actions\Projects\ResolveSampleRegistrationProject;
use App\ChangeTracking\ChangeTracker;
use App\Models\Project;
use App\Models\Sample;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

class CreateRegisteredSample
{
    public function __construct(
        private BarcodeGenerator $barcodes,
        private ResolveSampleRegistrationProject $projects,
        private FindClientDefaultProductGroup $productGroups,
        private ChangeTracker $changeTracker,
    ) {}

    public function handle(array $data, ?Project $project = null, ?CarbonInterface $at = null): Sample
    {
        return DB::transaction(function () use ($data, $project, $at): Sample {
            $at ??= now();
            DB::statement("select pg_advisory_xact_lock(hashtext('mesa-lims-sample-barcode'))");
            $matrix = ($data['sample_type'] ?? 'S') === 'L' ? $data['leg_type'] : null;
            $lastSample = $this->barcodes->sequence($matrix)->lockForUpdate()->first(['follow_no', 'date_registered']);
            $barcode = $this->barcodes->forLastSample($lastSample, $at, 0, $matrix);
            $project ??= $this->projects->handle($data, $barcode, $at);

            $sample = $project->samples()->create([
                'barcode' => $barcode,
                'follow_no' => $this->barcodes->followNumberForLastSample($lastSample, $at),
                'description' => $data['description'] ?? '',
                'client_description' => 'Geen omschrijving beschikbaar',
                'sampling_method' => $data['sampling_method'] ?? 0,
                'date_registered' => (string) $at->timestamp,
                'registered_by' => $data['registered_by'],
                'client' => $data['client'],
                'subclient' => 0,
                'custom_fields' => json_encode($data['custom_fields'] ?? [], JSON_FORCE_OBJECT),
                'predicted_end' => $at->timestamp,
                'sample_innoculated' => $data['sample_innoculated'] ?? '',
                'stored_in' => null,
                'sample_note' => $data['sample_note'] ?? null,
                'sample_type' => $data['sample_type'] ?? 'S',
                'leg_type' => $data['leg_type'] ?? '-',
                'sample_extra' => isset($data['sample_extra']) ? json_encode($data['sample_extra']) : null,
                'isEmpty' => 1,
                'source' => 0,
                'analyses_data' => '[]',
                'portal_product_group_id' => $this->productGroups->handle((int) $data['client'])?->portal_id,
            ]);

            $this->changeTracker->changed(2, project: $project->id, sample: $sample->id,
                event: 'Monster aangemaakt: '.$sample->barcode, from: false, to: false, userId: (int) $data['registered_by']);

            return $sample;
        });
    }
}
