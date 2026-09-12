<?php

namespace App\Jobs;

use App\Actions\Results\CalculateAnalysisResult;
use App\Calculations\Exceptions\ResultCalculationException;
use App\Events\AnalysisResultCalculated;
use App\Events\AnalysisResultCalculationFailed;
use App\Models\SampleAnalysis;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Throwable;

class CalculateAnalysisResultJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 10;

    public int $timeout = 120;

    public function __construct(public int $analysisId)
    {
        $this->onQueue('calculations');
    }

    public function middleware(): array
    {
        return [
            (new WithoutOverlapping("analysis-result:{$this->analysisId}"))
                ->releaseAfter(2)
                ->expireAfter(180),
        ];
    }

    public function handle(CalculateAnalysisResult $calculate): void
    {
        $analysis = SampleAnalysis::query()->find($this->analysisId);

        if ($analysis === null) {
            return;
        }

        try {
            $calculation = $calculate->handle($analysis);
        } catch (ResultCalculationException $exception) {
            report($exception);

            AnalysisResultCalculationFailed::dispatch(
                (int) $analysis->sample,
                $analysis->id,
                $exception->userMessage(),
            );

            return;
        }

        AnalysisResultCalculated::dispatch(
            (int) $analysis->sample,
            $analysis->id,
            $calculation,
        );
    }

    public function failed(?Throwable $exception): void
    {
        $analysis = SampleAnalysis::query()->find($this->analysisId);

        if ($analysis !== null) {
            AnalysisResultCalculationFailed::dispatch((int) $analysis->sample, $analysis->id);
        }
    }
}
