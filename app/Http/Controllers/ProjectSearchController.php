<?php

namespace App\Http\Controllers;

use App\Actions\Projects\GetProjectSearchDetails;
use App\Actions\Projects\GetProjectSearchSample;
use App\Actions\Projects\SearchProjects;
use App\Actions\Projects\UpdateProjectSearchSample;
use App\Actions\Results\GetAnalysisResults;
use App\Models\Project;
use App\Models\Sample;
use App\Models\SampleAnalysis;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ProjectSearchController extends Controller
{
    public function index(Request $request): View
    {
        return view('projects.search', [
            'permissions' => [
                'updateSample' => $request->user()->can('samples.update'),
            ],
        ]);
    }

    public function search(Request $request, SearchProjects $search): JsonResponse
    {
        $data = $request->validate([
            'mode' => ['required', Rule::in(['barcode', 'reference'])],
            'query' => ['required', 'string', 'max:128'],
        ]);

        return response()->json(['data' => $search->handle($data['mode'], trim($data['query']))]);
    }

    public function show(Project $project, GetProjectSearchDetails $details): JsonResponse
    {
        return response()->json(['data' => $details->handle($project)]);
    }

    public function showSample(Project $project, Sample $sample, GetProjectSearchSample $details): JsonResponse
    {
        return response()->json(['data' => $details->handle($project, $sample)]);
    }

    public function showSampleAnalysisResults(
        Project $project,
        Sample $sample,
        SampleAnalysis $analysis,
        GetAnalysisResults $results,
    ): JsonResponse {
        abort_unless(
            (int) $sample->project === (int) $project->id
                && (int) $analysis->sample === (int) $sample->id
                && (int) $analysis->project === (int) $project->id,
            404,
        );

        return response()->json(['data' => $results->handle($analysis)]);
    }

    public function updateSample(
        Request $request,
        Project $project,
        Sample $sample,
        UpdateProjectSearchSample $update,
        GetProjectSearchSample $details,
    ): JsonResponse {
        abort_unless($request->user()->can('samples.update'), 403);

        $data = $request->validate([
            'source' => ['required', Rule::in(['attribute', 'custom', 'extra'])],
            'field' => ['required', 'string', 'max:128'],
            'value' => ['nullable', 'string', 'max:65535'],
        ]);

        $update->handle($project, $sample, [...$data, 'value' => $data['value'] ?? '']);

        return response()->json(['data' => $details->handle($project->fresh(), $sample->fresh())]);
    }
}
