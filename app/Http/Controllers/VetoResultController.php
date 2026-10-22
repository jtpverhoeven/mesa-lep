<?php

namespace App\Http\Controllers;

use App\Actions\Results\ChangeVetoResult;
use App\Actions\Results\GetAnalysisResults;
use App\Http\Requests\ChangeVetoResultRequest;
use App\Models\Project;
use App\Models\Sample;
use App\Models\SampleAnalysis;
use Illuminate\Http\JsonResponse;

class VetoResultController extends Controller
{
    public function update(
        ChangeVetoResultRequest $request,
        Project $project,
        Sample $sample,
        SampleAnalysis $analysis,
        ChangeVetoResult $change,
        GetAnalysisResults $results,
    ): JsonResponse {
        $data = $request->validated();
        $change->handle(
            $project, $sample, $analysis, $data['parameter'],
            $request->isMethod('DELETE') ? null : $data['value'],
            $data['reason'], $data['disposition'] ?? null,
        );

        return response()->json(['data' => $results->handle($analysis)]);
    }
}
