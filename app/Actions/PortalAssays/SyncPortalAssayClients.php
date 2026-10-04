<?php

namespace App\Actions\PortalAssays;

use App\Models\Client;
use App\Models\PortalAssay;
use Illuminate\Support\Facades\DB;

class SyncPortalAssayClients
{
    public function handle(
        PortalAssay $portalAssay,
        array $selectedClientIds,
        bool $replaceAll = false,
    ): void {
        $activeClientIds = Client::query()->where('active', 1)->pluck('id');
        $selectedClientIds = collect($selectedClientIds)
            ->map(fn (mixed $id): int => (int) $id)
            ->intersect($activeClientIds)
            ->unique();

        DB::transaction(function () use ($portalAssay, $activeClientIds, $selectedClientIds, $replaceAll): void {
            $clientIds = $replaceAll
                ? $selectedClientIds
                : $portalAssay->clients()->pluck('clients.id')->diff($activeClientIds)->merge($selectedClientIds)->unique();

            $portalAssay->clients()->sync($clientIds->all());
        });
    }

    public function replace(PortalAssay $portalAssay, array $clientIds): void
    {
        DB::transaction(fn () => $portalAssay->clients()->sync($clientIds));
    }
}
