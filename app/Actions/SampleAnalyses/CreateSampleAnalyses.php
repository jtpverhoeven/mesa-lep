<?php

namespace App\Actions\SampleAnalyses;

use App\ChangeTracking\ChangeTracker;
use App\Models\Assay;
use App\Models\Project;
use App\Models\Sample;
use App\Models\SampleAnalysis;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CreateSampleAnalyses
{
    public function __construct(private RefreshSampleIsEmpty $refreshSampleIsEmpty, private ChangeTracker $changeTracker) {}

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
            $assayNames = Assay::query()->whereIn('id', array_column($definitions, 'assay_base'))->pluck('name', 'id');

            foreach ($definitions as $definition) {
                $analysis = SampleAnalysis::create([
                    'profile_group' => $profileGroup ?? 0,
                    'sample' => $sample->id,
                    'follow_number' => $followNumber++,
                    'profile' => $definition['profile'],
                    'assay' => $definition['assay'],
                    'assay_base' => $definition['assay_base'],
                    'roaming_id' => $definition['roaming_id'] ?? null,
                    'predicted_end' => $sample->predicted_end,
                    'original_assay_base' => $definition['original_assay_base'],
                    'conf_requested' => 0,
                    'is_ready' => 0,
                    'project' => $sample->project,
                    'project_order' => $definition['project_order'] ?? ++$projectOrder,
                    'storedResult' => null,
                ]);

                if ($profileGroup === null) {
                    $profileGroup = $analysis->id;
                    $analysis->update(['profile_group' => $profileGroup]);
                } else {
                    $analysis->update(['profile_group' => $profileGroup]);
                }

                $analyses->add($analysis);
                $this->changeTracker->changed(6, project: $sample->project, sample: $sample->id, said: $analysis->id,
                    event: 'Analyse '.($assayNames[$analysis->assay_base] ?? '').' toegevoegd aan monster '.$sample->barcode,
                    from: false, to: false, userId: auth()->id() ?? (int) $sample->registered_by);
            }

            $this->refreshSampleIsEmpty->handle($sample);

            return $analyses;
        });
    }
}
