<?php

namespace App\Actions\Sidebar;

use App\Events\SidebarCountsUpdated;
use Illuminate\Support\Facades\Cache;

class RefreshSidebarCounts
{
    public function __construct(private CalculateSidebarCounts $calculate) {}

    /** Recalculates, caches and broadcasts the counts when they changed since the cached copy. */
    public function handle(): array
    {
        $counts = $this->calculate->handle();
        $changed = Cache::get(GetSidebarCounts::CACHE_KEY) !== $counts;

        Cache::put(GetSidebarCounts::CACHE_KEY, $counts, GetSidebarCounts::TTL_SECONDS);

        if ($changed) {
            SidebarCountsUpdated::dispatch($counts);
        }

        return $counts;
    }
}
