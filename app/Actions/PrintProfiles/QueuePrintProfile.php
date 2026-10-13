<?php

namespace App\Actions\PrintProfiles;

use App\Jobs\RunPrintProfileJob;
use App\Models\PrintProfile;
use App\Models\Sample;

class QueuePrintProfile
{
    public function __construct(private ParsePrintProfile $parsePrintProfile) {}

    public function handle(PrintProfile $profile, Sample $sample, ?int $printerOverride = null, int $dateOffset = 0, string $currentUser = ''): void
    {
        $this->parsePrintProfile->handle($profile->profile);
        RunPrintProfileJob::dispatch($profile->id, $profile->profile, $sample->id, $printerOverride, $dateOffset, $currentUser);
    }
}
