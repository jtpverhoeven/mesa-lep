<?php

namespace App\Http\Controllers;

use App\Actions\Projects\BlockProjectAuthorization;
use App\Actions\Projects\UnblockProjectAuthorization;
use App\Models\Project;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProjectAuthorizationBlockController extends Controller
{
    public function store(Request $request, Project $project, BlockProjectAuthorization $block): JsonResponse
    {
        $data = $request->validate([
            'message' => ['nullable', 'string', 'max:1000'],
        ]);

        return $this->respond($block->handle($project, (int) $request->user()->id, $data['message'] ?? null));
    }

    public function destroy(Request $request, Project $project, UnblockProjectAuthorization $unblock): JsonResponse
    {
        return $this->respond($unblock->handle($project, (int) $request->user()->id));
    }

    private function respond(Project $project): JsonResponse
    {
        return response()->json(['data' => [
            'project' => $project->only(['id', 'auth_status', 'locked', 'locked_by', 'lock_message']),
            'read_only' => $project->isReadOnly(),
            'reasons' => $project->readOnlyReasons(),
        ]]);
    }
}
