<?php

namespace App\Actions\SampleAnalyses;

use App\Models\Project;
use App\Models\Sample;
use App\Models\SampleAnalysis;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CreateSampleAnalyses
{
    public function handle(Sample $sample, array $definitions): Collection
    {
        if ($definitions === []) {
            throw ValidationException::withMessages([
                'analyses' => 'Er zijn geen analyses geselecteerd.',
            ]);
        }

        return DB::transaction(function () use ($sample, $definitions) {
            $sample = Sample::query()->lockForUpdate()->findOrFail($sample->getKey());
            Project::query()->lockForUpdate()->findOrFail($sample->project);

            $followNumber = (int) SampleAnalysis::query()
                ->where('sample', $sample->id)
                ->max('follow_number') + 1;
            $projectOrder = (int) SampleAnalysis::query()
                ->where('project', $sample->project)
                ->max('project_order');
            $profileGroup = null;
            $analyses = new Collection;

            foreach ($definitions as $definition) {
                $analysis = SampleAnalysis::create([
                    'profile_group' => $profileGroup ?? 0,
                    'sample' => $sample->id,
                    'follow_number' => $followNumber++,
                    'profile' => $definition['profile'],
                    'assay' => $definition['assay'],
                    'assay_base' => $definition['assay_base'],
                    'roaming_id' => null,
                    'predicted_end' => $sample->predicted_end,
                    'original_assay_base' => $definition['original_assay_base'],
                    'conf_requested' => 0,
                    'is_ready' => 0,
                    'project' => $sample->project,
                    'project_order' => ++$projectOrder,
                    'storedResult' => null,
                ]);

                if ($profileGroup === null) {
                    $profileGroup = $analysis->id;
                    $analysis->update(['profile_group' => $profileGroup]);
                } else {
                    $analysis->update(['profile_group' => $profileGroup]);
                }

                $analyses->add($analysis);
            }

            $sample->update(['isEmpty' => 0]);

            return $analyses;
        });
    }
}
