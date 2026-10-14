<?php

namespace App\Jobs;

use App\Actions\Printing\SendLabelPrintPlan;
use App\Actions\PrintProfiles\BuildPrintProfilePlan;
use App\Models\PrintProfile;
use App\Models\Sample;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class RunPrintProfileJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public int $timeout = 60;

    public bool $failOnTimeout = true;

    /** @var array<string, mixed> */
    public array $activityContext = [];

    /** @param array<string, mixed> $activityContext */
    public function __construct(
        public int $profileId,
        public string $profileScript,
        public int $sampleId,
        public ?int $printerOverride = null,
        public int $dateOffset = 0,
        public string $currentUser = '',
        array $activityContext = [],
    ) {
        $this->activityContext = $activityContext;
        $this->onConnection(config('printing.connection', 'database'));
        $this->onQueue(config('printing.queue', 'printing'));
        $this->afterCommit();
    }

    public function handle(BuildPrintProfilePlan $buildPrintProfilePlan, SendLabelPrintPlan $sendLabelPrintPlan): void
    {
        $profile = new PrintProfile(['profile' => $this->profileScript]);
        $profile->id = $this->profileId;
        $sample = Sample::query()->findOrFail($this->sampleId);
        $plan = $buildPrintProfilePlan->handle($profile, $sample, $this->printerOverride, dateOffset: $this->dateOffset, currentUser: $this->currentUser);

        $context = $this->logContext();
        $spools = DB::transaction(fn (): array => $sendLabelPrintPlan->handle($plan['labels'], $context));
        Log::channel('printing')->info('Print profile processed.', [...$context, 'total_labels' => $plan['total_labels'], 'spools' => $spools]);
    }

    public function failed(?Throwable $exception): void
    {
        Log::channel('printing')->error('Print profile failed.', [...$this->logContext(), 'error' => $exception?->getMessage()]);
    }

    /** @return array<string, mixed> */
    private function logContext(): array
    {
        return array_replace([
            'user_id' => null,
            'user_name' => $this->currentUser !== '' ? $this->currentUser : null,
        ], $this->activityContext, ['profile_id' => $this->profileId, 'sample_id' => $this->sampleId]);
    }
}
