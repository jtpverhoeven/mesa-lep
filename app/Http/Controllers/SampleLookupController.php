<?php

namespace App\Http\Controllers;

use App\Actions\SampleAnalyses\GetSampleAnalysisOptions;
use App\Actions\Samples\LookupSample;
use App\Actions\Samples\UpdateLookupResearch;
use App\Http\Requests\StoreSampleRequest;
use App\Models\Sample;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SampleLookupController extends Controller
{
    public function index(): View
    {
        return view('samples.lookup');
    }

    public function show(Request $request, LookupSample $lookup): JsonResponse
    {
        $data = $request->validate(['barcode' => ['required', 'string', 'max:32']]);

        return response()->json(['data' => $lookup->handle(trim($data['barcode']))]);
    }

    public function options(Sample $sample, GetSampleAnalysisOptions $options): JsonResponse
    {
        return response()->json(['data' => $options->handle($sample->client)]);
    }

    public function update(Request $request, Sample $sample, UpdateLookupResearch $update, LookupSample $lookup): JsonResponse
    {
        $operation = $request->validate(['operation' => ['required', Rule::in(['add', 'remove', 'reorder'])]])['operation'];
        abort_unless($request->user()->can($operation === 'add' ? 'samples.assign-research' : 'samples.update-research'), 403);
        $rules = match ($operation) {
            'add' => [...array_filter((new StoreSampleRequest)->rules(), fn ($key) => str_starts_with($key, 'analyses.'), ARRAY_FILTER_USE_KEY), 'analyses' => ['required', 'array', 'min:1']],
            'remove' => ['analysis_id' => ['required', 'integer']],
            'reorder' => ['analysis_ids' => ['required', 'array', 'min:1'], 'analysis_ids.*' => ['required', 'integer', 'distinct']],
        };
        $update->handle($sample, ['operation' => $operation, ...$request->validate($rules)]);

        return response()->json(['data' => $lookup->handle($sample->barcode)]);
    }
}
