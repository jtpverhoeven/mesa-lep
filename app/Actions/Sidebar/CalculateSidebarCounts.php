<?php

namespace App\Actions\Sidebar;

use App\Actions\AssuranceForms\QueryFormsAwaitingData;
use App\Actions\Projects\GetProjectOverview;
use App\Actions\Samples\QueryNotInoculatedSamples;
use App\Models\Project;
use App\Models\Sample;
use App\Models\SampleBuffer;
use Illuminate\Support\Facades\Date;

class CalculateSidebarCounts
{
    public const PROJECT_STATUSES = ['received', 'running', 'completed', 'authorized', 'blocked'];

    public function __construct(
        private QueryNotInoculatedSamples $notInoculated,
        private QueryFormsAwaitingData $formsAwaitingData,
    ) {}

    /**
     * Open work per sidebar section. Every query reuses the scope or filter that backs the page it links to.
     *
     * @return array{buffer: int, tht: int, awaiting_analysis: int, not_inoculated: int, received: int, running: int, completed: int, authorized: int, blocked: int, assurance: int}
     */
    public function handle(): array
    {
        $projects = [];
        foreach (self::PROJECT_STATUSES as $status) {
            $projects[$status] = Project::query()->where(GetProjectOverview::FILTERS[$status])->count();
        }

        return [
            'buffer' => SampleBuffer::query()->parked()->count(),
            'tht' => SampleBuffer::query()->thtDueBefore(Date::now('Europe/Amsterdam')->addDay()->format('Y-m-d'))->count(),
            'awaiting_analysis' => Sample::query()->awaitingAnalysis()->count(),
            'not_inoculated' => $this->notInoculated->handle()->count(),
            ...$projects,
            'assurance' => $this->formsAwaitingData->handle()->count(),
        ];
    }
}
