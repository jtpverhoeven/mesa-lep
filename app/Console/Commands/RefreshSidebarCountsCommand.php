<?php

namespace App\Console\Commands;

use App\Actions\Sidebar\RefreshSidebarCounts;
use Illuminate\Console\Command;

class RefreshSidebarCountsCommand extends Command
{
    protected $signature = 'sidebar:refresh-counts';

    protected $description = 'Recalculate the sidebar status badges and broadcast them when they changed';

    public function handle(RefreshSidebarCounts $refresh): int
    {
        $this->info(json_encode($refresh->handle()));

        return self::SUCCESS;
    }
}
