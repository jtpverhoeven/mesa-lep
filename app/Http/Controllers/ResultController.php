<?php

namespace App\Http\Controllers;

use App\Actions\Results\GetAnalysisResults;
use App\Actions\Results\UpdateResultValue;
use App\Http\Requests\UpdateResultRequest;
use App\Jobs\CalculateAnalysisResultJob;
use App\Models\Result;
use App\Models\SampleAnalysis;
use Illuminate\Http\JsonResponse;

class ResultController extends Controller
{
    public function index(SampleAnalysis $sampleAnalysis, GetAnalysisResults $results): JsonResponse
    {
        return response()->json(['data' => $results->handle($sampleAnalysis)]);
    }

    public function update(
        UpdateResultRequest $request,
        SampleAnalysis $sampleAnalysis,
        Result $result,
        UpdateResultValue $update,
    ): JsonResponse {
        $data = $request->validated();
        $result = $update->handle($sampleAnalysis, $result, $data['field'], $data['value'] ?? '');
        CalculateAnalysisResultJob::dispatch($sampleAnalysis->id)->afterCommit();

        return response()->json([
            'data' => $result->only([
                'id', 'sample', 'sa_id', 'follow_no', 'profile', 'assay', 'assay_base',
                'roaming_id', 'df', 'rep', 'data',
            ]),
            'calculation' => [
                'status' => 'queued',
                'sample_id' => (int) $sampleAnalysis->sample,
                'analysis_id' => $sampleAnalysis->id,
            ],
        ], 202);
    }
}
