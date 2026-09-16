<?php

namespace App\Jobs;

use App\Actions\AssuranceForms\GetOrCreateAssuranceForm;
use App\Actions\AssuranceForms\ResolveAssuranceDay;
use App\Actions\AssuranceForms\SynchronizeAssuranceForm;
use App\Models\AssuranceForm;
use Illuminate\Contracts\Queue\ShouldBeUniqueUntilProcessing;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
use Throwable;

class SynchronizeAssuranceFormJob implements ShouldBeUniqueUntilProcessing, ShouldQueue
{
    use Dispatchable, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public int $tries = 5;

    public int $timeout = 60;

    public int $uniqueFor = 300;

    public function __construct(public string $formDate) {}

    /**
     * Execute the job.
     */
    public function uniqueId(): string
    {
        return $this->formDate;
    }

    public function middleware(): array
    {
        return [
            (new WithoutOverlapping('assurance-form:'.$this->formDate))
                ->releaseAfter(2)
                ->expireAfter(75),
        ];
    }

    public function handle(
        GetOrCreateAssuranceForm $getOrCreate,
        ResolveAssuranceDay $resolveDay,
        SynchronizeAssuranceForm $synchronize,
    ): void {
        $day = $resolveDay->handle($this->formDate);
        $form = AssuranceForm::query()->where('date', $day['timestamp'])->first();

        if ($form === null) {
            if (! $synchronize->hasSamplesForDay($day['form_date'])) {
                return;
            }

            $form = $getOrCreate->handle($day['form_date']);
        }

        $synchronize->handle($form);
    }

    public function failed(?Throwable $exception): void
    {
        report($exception);
    }
}
