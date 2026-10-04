<?php

namespace App\Actions\PortalAssays;

use App\Models\Client;
use App\Models\PortalAssay;
use Illuminate\Support\Facades\DB;

class CreatePortalAssay
{
    public function handle(array $data): PortalAssay
    {
        return DB::transaction(function () use ($data) {
            $portalAssay = PortalAssay::create([
                'common_name' => $data['common_name'],
                'common_name_en' => $data['common_name_en'] ?? null,
                'selectable' => $data['selectable'],
                'alertable' => $data['alertable'],
                'border_reaction' => $data['border_reaction'],
                'active' => 1,
            ]);

            if ($data['add_to_all_clients'] ?? false) {
                $portalAssay->clients()->sync(Client::query()->where('active', 1)->pluck('id')->all());
            }

            return $portalAssay;
        });
    }
}
