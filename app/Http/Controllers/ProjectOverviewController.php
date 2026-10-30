<?php

namespace App\Http\Controllers;

use App\Actions\Projects\GetProjectOverview;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProjectOverviewController extends Controller
{
    public function index(string $status): View
    {
        return view('projects.overview', [
            'status' => $status,
            'title' => GetProjectOverview::STATUSES[$status],
        ]);
    }

    public function data(Request $request, string $status, GetProjectOverview $overview): JsonResponse
    {
        $data = $request->validate(['page' => ['sometimes', 'integer', 'min:1']]);

        $canGenerate = $request->user()->can('reports.generate');

        return response()->json($overview->handle($status, (int) ($data['page'] ?? 1))->through(
            fn (array $project): array => [
                ...$project,
                'report_url' => $canGenerate ? route('projects.reports.index', $project['id']) : null,
                'temporary_report_url' => $canGenerate ? route('projects.reports.temporary.index', $project['id']) : null,
            ],
        ));
    }
}
