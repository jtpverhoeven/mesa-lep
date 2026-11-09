<?php

namespace App\Http\Controllers;

use App\Actions\Reports\ShowFileSender;
use App\Actions\SampleFiles\SendProjectFiles;
use App\Http\Requests\SendProjectFilesRequest;
use App\Models\Project;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class FileSenderController extends Controller
{
    public function show(int $project, ShowFileSender $showFileSender): View|RedirectResponse
    {
        $project = Project::query()->find($project);

        if ($project === null) {
            return to_route('projects.overview.completed');
        }

        return view('reports.send-files', ['config' => $showFileSender->handle($project), 'project' => $project]);
    }

    public function mail(SendProjectFilesRequest $request, Project $project, SendProjectFiles $sendProjectFiles): JsonResponse
    {
        set_time_limit(300);

        $result = $sendProjectFiles->handle($project, $request->hashes(), $request->mailInput());

        return response()->json($result, isset($result['status']) ? 502 : 200);
    }
}
