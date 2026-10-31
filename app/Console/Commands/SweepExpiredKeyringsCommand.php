<?php

namespace App\Console\Commands;

use App\Actions\Keyrings\SweepExpiredKeyrings;
use Illuminate\Console\Command;

class SweepExpiredKeyringsCommand extends Command
{
    protected $signature = 'keyrings:sweep';

    protected $description = 'Release expired keyrings whose holders are no longer present';

    public function handle(SweepExpiredKeyrings $sweep): int
    {
        $counts = $sweep->handle();
        $this->info("Released: {$counts['released']}, extended: {$counts['extended']}.");

        return self::SUCCESS;
    }
}
