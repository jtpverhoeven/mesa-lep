<?php

namespace App\Actions\Samples;

use App\Actions\Printing\DispatchLabelEvents;
use App\Actions\Projects\ResolveProjectReference;
use App\Models\Project;
use Carbon\CarbonImmutable;
use Closure;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class CreateBulkSamples
{
    public function __construct(private CreateRegisteredSample $create, private ResolveProjectReference $reference, private DispatchLabelEvents $dispatchLabelEvents) {}

    public function handle(array $data, Closure $afterCreate): Collection
    {
        return DB::transaction(function () use ($data, $afterCreate): Collection {
            $at = now();
            $project = null;
            $samples = new Collection;
            $inoculation = empty($data['inoculation_date']) ? '' : (string) CarbonImmutable::createFromFormat(
                '!Y-m-d H:i', $data['inoculation_date'].' '.($data['inoculation_time'] ?? '00:00'), config('app.timezone'),
            )->timestamp;

            foreach ($data['samples'] as $row) {
                $sample = $this->create->handle([
                    ...$data,
                    ...$row,
                    'sample_innoculated' => $inoculation,
                ], $project, $at, printLabels: false);
                $project ??= $sample->project()->firstOrFail();
                $afterCreate($sample, $row);
                $samples->add($sample->fresh());
            }

            if ($project !== null) {
                $this->updateReference($project);
            }

            $samples->load('analyses');
            $this->dispatchLabelEvents->handle($samples->concat($samples->flatMap(fn ($sample) => $sample->analyses)));

            return $samples;
        });
    }

    private function updateReference(Project $project): void
    {
        $reference = $this->reference->handle($project);

        if ($reference !== null) {
            $project->update(['reference' => $reference]);
        }
    }
}
