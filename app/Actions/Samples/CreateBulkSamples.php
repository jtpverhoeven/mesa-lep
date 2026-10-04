<?php

namespace App\Actions\Samples;

use App\Models\Project;
use Carbon\CarbonImmutable;
use Closure;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class CreateBulkSamples
{
    public function __construct(private CreateRegisteredSample $create, private BulkSampleRegistrationOptions $options) {}

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
                ], $project, $at);
                $project ??= $sample->project()->firstOrFail();
                $afterCreate($sample, $row);
                $samples->add($sample->fresh());
            }

            if ($project !== null) {
                $this->updateReference($project);
            }

            return $samples;
        });
    }

    private function updateReference(Project $project): void
    {
        $firstSample = $project->samples()->oldest('id')->first(['id', 'barcode']);
        $reference = match ($this->options->value('MESA_PROJECT_REFERENCE')) {
            '1' => $project->id,
            '2' => $firstSample?->id,
            '3' => $firstSample?->barcode,
            default => null,
        };

        if ($reference !== null) {
            $project->update(['reference' => $reference]);
        }
    }
}
