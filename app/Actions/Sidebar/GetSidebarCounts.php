<?php

namespace App\Actions\Sidebar;

use Illuminate\Support\Facades\Cache;

class GetSidebarCounts
{
    public const CACHE_KEY = 'sidebar-counts';

    /** Safety net for when the scheduled refresh is not running; the refresh normally rewrites the entry long before. */
    public const TTL_SECONDS = 60;

    public function __construct(private CalculateSidebarCounts $calculate) {}

    /** @return array<string, int> */
    public function handle(): array
    {
        return Cache::remember(self::CACHE_KEY, self::TTL_SECONDS, fn (): array => $this->calculate->handle());
    }
}
