<?php

namespace App\Actions\Results;

use App\Models\Result;
use App\Models\SampleAnalysis;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class CreateAnalysisResults
{
    public function handle(SampleAnalysis $analysis): Collection
    {
        return DB::transaction(function () use ($analysis) {
            $analysis = SampleAnalysis::query()
                ->with(['assayRecord.assayType.fields', 'assayProfile', 'roamingAnalysis'])
                ->lockForUpdate()
                ->findOrFail($analysis->getKey());

            $existing = Result::query()
                ->where('sa_id', $analysis->id)
                ->orderByDesc('df')
                ->orderBy('rep')
                ->get();

            if ($existing->isNotEmpty()) {
                return $existing;
            }

            $assay = $analysis->assayRecord;
            $settings = $analysis->isRoaming() ? $analysis->roamingAnalysis : $analysis->assayProfile;

            if ($assay === null || $settings === null) {
                return new Collection;
            }

            $dilutions = array_values($settings->dillutions ?? []);
            if ($dilutions === []) {
                if ((int) $assay->type === 4) {
                    return new Collection;
                }

                $dilutions = [1];
            }

            $dilutions = array_map('floatval', $dilutions);
            rsort($dilutions, SORT_NUMERIC);

            $data = $assay->assayType?->fields
                ->pluck('name')
                ->mapWithKeys(fn (string $name) => [$name => ''])
                ->all() ?? [];
            $replicates = max(0, (int) $settings->replicates);
            $followNumber = 1;
            $results = new Collection;

            foreach ($dilutions as $dilution) {
                for ($replicate = 0; $replicate <= $replicates; $replicate++) {
                    $results->add(Result::create([
                        'sample' => $analysis->sample,
                        'sa_id' => $analysis->id,
                        'follow_no' => $followNumber++,
                        'profile' => $analysis->profile,
                        'assay' => $analysis->assay,
                        'assay_base' => $analysis->assay_base,
                        'roaming_id' => $analysis->roaming_id ?? 0,
                        'df' => $this->formatDilution($dilution),
                        'rep' => $replicate,
                        'data' => (object) $data,
                    ]));
                }
            }

            return $results;
        });
    }

    private function formatDilution(float $dilution): string
    {
        return rtrim(rtrim(sprintf('%.10F', $dilution), '0'), '.');
    }
}
