<?php

namespace App\Actions\PortalAssays;

use App\Models\PortalAssay;
use Illuminate\Support\Facades\DB;

class UpdatePortalAssay
{
    public function handle(PortalAssay $portalAssay, array $data): PortalAssay
    {
        return DB::transaction(function () use ($portalAssay, $data) {
            $portalAssay->update([
                'common_name' => $data['common_name'],
                'common_name_en' => $data['common_name_en'] ?? null,
                'selectable' => $data['selectable'],
                'alertable' => $data['alertable'],
                'border_reaction' => $data['border_reaction'],
            ]);

            return $portalAssay->fresh();
        });
    }
}
