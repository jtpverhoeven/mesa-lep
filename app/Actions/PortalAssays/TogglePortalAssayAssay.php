<?php

namespace App\Actions\PortalAssays;

use App\Models\Assay;
use App\Models\PortalAssay;
use App\Models\PortalAssayContent;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TogglePortalAssayAssay
{
    public function handle(PortalAssay $portalAssay, Assay $assay, bool $attached): bool
    {
        return DB::transaction(function () use ($portalAssay, $assay, $attached): bool {
            $content = PortalAssayContent::query()
                ->where('assay_id', $assay->id)
                ->lockForUpdate()
                ->first();

            if (! $attached) {
                if ($content?->common_id === $portalAssay->id) {
                    $content->delete();
                }

                return false;
            }

            if ($content !== null && $content->common_id !== $portalAssay->id) {
                throw ValidationException::withMessages([
                    'assay_id' => 'Deze analyse is al gekoppeld aan een andere portaalanalyse.',
                ]);
            }

            if ($content === null) {
                PortalAssayContent::create([
                    'assay_id' => $assay->id,
                    'original_id' => $assay->original_id ?: $assay->id,
                    'common_id' => $portalAssay->id,
                ]);
            }

            return true;
        });
    }
}
