<?php

namespace App\Http\Controllers;

use App\Actions\Notes\PresentNote;
use App\Actions\Notes\SaveReportNote;
use App\Http\Requests\SaveReportNoteRequest;
use App\Models\Project;
use App\Models\Sample;
use Illuminate\Http\JsonResponse;

class ReportNoteController extends Controller
{
    public function updateProject(SaveReportNoteRequest $request, Project $project, SaveReportNote $save, PresentNote $present): JsonResponse
    {
        $note = $save->handle($project, null, $request->validated('body') ?? '', $request->user()->id);

        return response()->json(['data' => $present->optional($note)]);
    }

    public function updateSample(SaveReportNoteRequest $request, Project $project, Sample $sample, SaveReportNote $save, PresentNote $present): JsonResponse
    {
        $note = $save->handle($project, $sample, $request->validated('body') ?? '', $request->user()->id);

        return response()->json(['data' => $present->optional($note)]);
    }
}
