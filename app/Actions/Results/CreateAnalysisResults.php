<?php

namespace App\Actions\Results;

use App\Models\Result;
use App\Models\SampleAnalysis;
use App\Support\DilutionCurve;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class CreateAnalysisResults
{
    public function __construct(private CreateResultRow $createRow) {}

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

            $data = $assay->emptyResultData();
            $replicates = max(0, (int) $settings->replicates);
            $followNumber = 1;
            $results = new Collection;

            foreach ($dilutions as $dilution) {
                for ($replicate = 0; $replicate <= $replicates; $replicate++) {
                    $results->add($this->createRow->handle($analysis, DilutionCurve::format($dilution), $replicate, $followNumber++, $data));
                }
            }

            return $results;
        });
    }
}
