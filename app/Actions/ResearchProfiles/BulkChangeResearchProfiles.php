<?php

namespace App\Actions\ResearchProfiles;

use App\Models\ResearchProfile;
use Illuminate\Support\Facades\DB;

class BulkChangeResearchProfiles
{
    public function handle(array $data): int
    {
        return DB::transaction(function () use ($data) {
            $changed = 0;
            $profiles = ResearchProfile::query()->whereIn('id', $data['profiles'])->where('active', 1)->get();

            foreach ($profiles as $profile) {
                $assays = $profile->assayProfiles()->where('hidden', 0)->orderBy('project_order')->get();
                $position = $assays->search(fn ($item) => (int) $item->assay === (int) $data['assay']);

                if ($data['mutation'] !== 'add' && $position === false) {
                    continue;
                }

                $revision = $profile->replicate();
                $revision->save();

                if ($data['mutation'] === 'remove') {
                    $assays->forget($position);
                } elseif ($data['mutation'] === 'swap') {
                    $assays[$position]->assay = $data['replacement_assay'];
                } else {
                    $settings = $data['settings'];
                    $newAssay = [
                        'assay' => $data['assay'], 'dillutions' => $settings['dillutions'] ?? [],
                        'replicates' => $settings['replicates'] ?? 0, 'reference' => $settings['reference'] ?? [],
                        'hidden' => 0, 'conf_trip' => $settings['conf_trip'] ?? 0,
                        'reference_source' => $settings['reference_source'] ?? null,
                    ];
                    $position === false ? $assays->push($newAssay) : $assays->put($position, $newAssay);
                }

                $revision->assayProfiles()->createMany($assays->values()->map(function ($assay, int $index) {
                    $attributes = is_array($assay) ? $assay : $assay->only([
                        'assay', 'dillutions', 'replicates', 'reference', 'hidden', 'conf_trip', 'reference_source',
                    ]);

                    return [...$attributes, 'project_order' => $index + 1];
                })->all());
                $profile->update(['active' => 0]);
                $changed++;
            }

            return $changed;
        });
    }
}