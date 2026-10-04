<?php

namespace App\Actions\PortalAssays;

use App\Models\Assay;
use App\Models\PortalAssayContent;

class AssociateAssayRevisionWithPortalAssays
{
    public function handle(Assay $previousAssay, Assay $revisedAssay): void
    {
        $contents = PortalAssayContent::query()
            ->where('assay_id', $previousAssay->id)
            ->get(['common_id']);

        foreach ($contents as $content) {
            PortalAssayContent::create([
                'assay_id' => $revisedAssay->id,
                'original_id' => $revisedAssay->original_id ?: $revisedAssay->id,
                'common_id' => $content->common_id,
            ]);
        }
    }
}
