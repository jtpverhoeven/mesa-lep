<?php

namespace App\Actions\PrintProfiles;

use App\Jobs\RunPrintProfileJob;
use App\Models\PrintProfile;
use App\Models\Sample;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class QueuePrintProfile
{
    public function __construct(private ParsePrintProfile $parsePrintProfile) {}

    public function handle(PrintProfile $profile, Sample $sample, ?int $printerOverride = null, int $dateOffset = 0, string $currentUser = ''): void
    {
        $this->parsePrintProfile->handle($profile->profile);
        $context = [
            'user_id' => Auth::id(),
            'user_name' => Auth::user()?->name ?? ($currentUser !== '' ? $currentUser : null),
            'profile_id' => $profile->id,
            'sample_id' => $sample->id,
        ];
        RunPrintProfileJob::dispatch($profile->id, $profile->profile, $sample->id, $printerOverride, $dateOffset, $currentUser, $context);
        Log::channel('printing')->info('Print profile queued.', $context);
    }
}
